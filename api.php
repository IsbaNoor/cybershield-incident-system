<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/validator.php';
require_once 'includes/ai_classifier.php';
require_once 'classes/UserManager.php';
require_once 'classes/IncidentManager.php';
require_once 'classes/AlertManager.php';

header('Content-Type: application/json');

// CSRF token generation if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['action'])) {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

$action = $input['action'];
$data = $input['data'] ?? [];

// Helper to require CSRF token for state-changing actions
function requireCsrf(array $data): void {
    if (empty($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Invalid CSRF token']);
        exit;
    }
}

try {
    switch ($action) {

        // ---------------- Authentication ----------------
        case 'register':
            $name = sanitize($data['full_name'] ?? '');
            $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $pass = $data['password'] ?? '';
            if (!$email || strlen($pass) < 8) {
                echo json_encode(['status'=>'error','message'=>'Valid email and password (min 8 chars) required']);
                exit;
            }
            $id = UserManager::register($name, $email, $pass);
            $_SESSION['user_id'] = $id;
            $_SESSION['user_role'] = 'employee'; // default
            echo json_encode(['status'=>'success','message'=>'Account created']);
            break;

        case 'login':
            $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $pass = $data['password'] ?? '';
            $user = UserManager::login($email, $pass);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                echo json_encode(['status'=>'success','message'=>'Login successful','data'=>['role'=>$user['role']]]);
            } else {
                echo json_encode(['status'=>'error','message'=>'Invalid credentials']);
            }
            break;

        case 'logout':
            session_destroy();
            echo json_encode(['status'=>'success','message'=>'Logged out']);
            break;

        // ---------------- Incident Reporting ----------------
        case 'reportIncident':
            requireLogin();
            requireCsrf($data);
            $title = sanitize($data['title'] ?? '');
            $desc = sanitize($data['description'] ?? '');
            $err = validateLength($desc, 10, 5000, 'Description');
            if ($err) {
                echo json_encode(['status'=>'error','message'=>$err]);
                exit;
            }
            list($cat, $sev) = AIClassifier::analyze($desc);
            $incidentId = IncidentManager::create($_SESSION['user_id'], $title, $desc, $cat, $sev);
            echo json_encode([
                'status'=>'success',
                'message'=>'Incident reported',
                'data'=>[
                    'incident_id'=>$incidentId,
                    'assigned_category'=>$cat,
                    'assigned_severity'=>$sev,
                    'created_at'=>date('c')
                ]
            ]);
            break;

        // ---------------- Incidents List (role-aware) ----------------
        case 'getIncidents':
            requireLogin();
            $user = getCurrentUser();
            if ($user['role'] === 'employee') {
                $incidents = IncidentManager::getByUser($user['id']);
            } else {
                $incidents = IncidentManager::getAll(); // analysts / admin see all
            }
            echo json_encode(['status'=>'success','data'=>$incidents]);
            break;

        case 'getIncidentDetail':
            requireLogin();
            $id = (int)($data['id'] ?? 0);
            $incident = IncidentManager::getById($id);
            if (!$incident) {
                echo json_encode(['status'=>'error','message'=>'Incident not found']);
                exit;
            }
            echo json_encode(['status'=>'success','data'=>$incident]);
            break;

        // ---------------- Incident Actions (Analyst / Admin) ----------------
        case 'updateIncident':
            requireLogin();
            requireCsrf($data);
            $user = getCurrentUser();
            if (!in_array($user['role'], ['analyst','admin'])) {
                http_response_code(403);
                echo json_encode(['status'=>'error','message'=>'Permission denied']);
                exit;
            }
            $id = (int)($data['incident_id'] ?? 0);
            $status = $data['status'] ?? '';
            IncidentManager::updateStatus($id, $status);
            $comment = trim($data['comment'] ?? '');
            if ($comment !== '') {
                IncidentManager::addComment($id, $user['id'], $comment);
            }
            echo json_encode(['status'=>'success','message'=>'Incident updated']);
            break;

        case 'addComment':
            requireLogin();
            requireCsrf($data);
            $id = (int)($data['incident_id'] ?? 0);
            $comment = sanitize($data['comment'] ?? '');
            IncidentManager::addComment($id, $_SESSION['user_id'], $comment);
            echo json_encode(['status'=>'success','message'=>'Comment added']);
            break;

        // ---------------- Threat Alerts ----------------
        case 'createAlert':
            requireLogin();
            requireRole('admin');
            requireCsrf($data);
            $title = sanitize($data['title'] ?? '');
            $message = sanitize($data['message'] ?? '');
            $severity = in_array($data['severity'] ?? '', ['Low','Medium','High','Critical']) ? $data['severity'] : 'Medium';
            $alertId = AlertManager::createAlert($title, $message, $severity, $_SESSION['user_id']);
            echo json_encode(['status'=>'success','message'=>'Alert broadcasted','data'=>['alert_id'=>$alertId]]);
            break;

        case 'pollAlerts':
            requireLogin();
            $lastId = (int)($data['last_alert_id'] ?? 0);
            $alerts = AlertManager::getNewAlerts($lastId);
            echo json_encode(['status'=>'success','alerts'=>$alerts, 'server_time'=>date('c')]);
            break;

        // ---------------- Dashboard Stats ----------------
        case 'getDashboardStats':
            requireLogin();
            $db = getDB();
            $userId = $_SESSION['user_id'];
            $role = $_SESSION['user_role'];
            if ($role === 'employee') {
                $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM incidents WHERE reported_by = ? GROUP BY status");
                $stmt->execute([$userId]);
            } else {
                $stmt = $db->query("SELECT status, COUNT(*) as count FROM incidents GROUP BY status");
            }
            $stats = $stmt->fetchAll();
            // Recent alerts
            $alertStmt = $db->query("SELECT * FROM alerts ORDER BY id DESC LIMIT 5");
            $recentAlerts = $alertStmt->fetchAll();
            echo json_encode(['status'=>'success','data'=>['stats'=>$stats, 'recent_alerts'=>$recentAlerts]]);
            break;

        default:
            echo json_encode(['status'=>'error','message'=>'Unknown action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Server error: ' . $e->getMessage()]);
}