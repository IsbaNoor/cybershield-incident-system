<?php
session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT id, full_name, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['status'=>'error','message'=>'Authentication required']);
        exit;
    }
}

function requireRole(string $role): void {
    $user = getCurrentUser();
    if (!$user || $user['role'] !== $role) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Insufficient permissions']);
        exit;
    }
}