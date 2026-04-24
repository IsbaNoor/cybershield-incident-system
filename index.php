<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
$loggedIn = isLoggedIn();
$user = $loggedIn ? getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cyber Incident & Threat Alert System | COMP-351</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Share+Tech+Mono&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
/* ═══════════ LIGHT CYBER THEME ═══════════ */
:root {
  --bg-light: #eaf5ff;               /* soft light blue background */
  --bg-panel: rgba(255,255,255,0.8);
  --card-bg: #ffffff;
  --accent-cyan: #00a6c0;            /* toned down cyan for light theme */
  --accent-blue: #1d4ed8;
  --accent-green: #059669;
  --accent-red: #dc2626;
  --accent-gold: #b45309;
  --text-primary: #0f172a;
  --text-secondary: #334155;
  --text-muted: #64748b;
  --border-glow: rgba(29,78,216,0.25);
  --border-subtle: rgba(29,78,216,0.12);
  --grid-color: rgba(29,78,216,0.06);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html { scroll-behavior: smooth; }

body {
  font-family: 'Exo 2', sans-serif;
  background: var(--bg-light);
  color: var(--text-primary);
  overflow-x: hidden;
  position: relative;
  display: flex;
  min-height: 100vh;
}

/* ── CANVAS BACKGROUND (light data particles) ── */
#particle-canvas {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  z-index: 0;
  opacity: 0.35;
  pointer-events: none;
}

/* ── GRID OVERLAY ── */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image:
    linear-gradient(var(--grid-color) 1px, transparent 1px),
    linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
  background-size: 60px 60px;
  z-index: 1;
  pointer-events: none;
}

/* ── SCANLINE OVERLAY (subtle white) ── */
body::after {
  content: '';
  position: fixed;
  inset: 0;
  background: repeating-linear-gradient(
    0deg,
    transparent,
    transparent 3px,
    rgba(255,255,255,0.03) 3px,
    rgba(255,255,255,0.03) 6px
  );
  z-index: 1;
  pointer-events: none;
}

/* ── MAIN WRAPPER ── */
.site-wrap { position: relative; z-index: 2; display: flex; width: 100%; }

/* ── SIDEBAR (inspired by navbar) ── */
.sidebar {
  width: 270px;
  background: rgba(255,255,255,0.9);
  backdrop-filter: blur(15px);
  border-right: 1px solid var(--border-subtle);
  display: flex;
  flex-direction: column;
  padding: 30px 20px;
  position: fixed;
  top: 0; left: 0; bottom: 0;
  z-index: 100;
  box-shadow: 4px 0 25px rgba(29,78,216,0.08);
  transition: width 0.3s ease;
}
.sidebar:hover { width: 280px; }

.sidebar .logo {
  font-family: 'Orbitron', sans-serif;
  font-size: 1.5rem;
  font-weight: 900;
  color: var(--accent-blue);
  margin-bottom: 35px;
  display: flex;
  align-items: center;
  gap: 10px;
  animation: fadeSlideRight 0.6s ease-out;
}
.sidebar .logo span { color: var(--accent-cyan); }

.sidebar nav a {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--text-secondary);
  text-decoration: none;
  padding: 12px 18px;
  border-radius: 10px;
  margin-bottom: 8px;
  font-weight: 600;
  transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
}
.sidebar nav a:hover {
  background: rgba(29,78,216,0.08);
  color: var(--accent-blue);
  transform: translateX(6px);
}
.sidebar nav a.active {
  background: linear-gradient(135deg, rgba(29,78,216,0.12), rgba(0,166,192,0.08));
  color: var(--accent-blue);
  font-weight: 700;
}

.sidebar .user-info {
  border-top: 1px solid var(--border-subtle);
  padding-top: 20px;
  margin-top: auto;
}
.sidebar .user-info p { font-size: 0.85rem; color: var(--text-muted); }
.sidebar .user-info strong { color: var(--text-primary); }
.sidebar .logout-btn {
  background: var(--accent-red);
  color: white;
  border: none;
  padding: 12px;
  width: 100%;
  border-radius: 10px;
  margin-top: 15px;
  cursor: pointer;
  font-weight: 700;
  transition: all 0.3s ease;
  animation: subtlePulse 2s infinite;
}
.sidebar .logout-btn:hover { background: #b91c1c; transform: scale(1.02); }

/* ── MAIN CONTENT ── */
.main-content {
  margin-left: 270px;
  width: calc(100% - 270px);
  padding: 35px;
  min-height: 100vh;
  transition: margin-left 0.3s ease;
}

/* ── HERO-ESQUE HEADER ── */
.page-header {
  background: linear-gradient(135deg, #1e3a8a, #3b82f6);
  color: white;
  padding: 30px 35px;
  border-radius: 18px;
  margin-bottom: 35px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 15px 35px rgba(29,78,216,0.2);
  animation: fadeInDown 0.6s ease-out;
  position: relative;
  overflow: hidden;
}
.page-header::before {
  content: '';
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    45deg,
    transparent,
    transparent 35px,
    rgba(255,255,255,0.03) 35px,
    rgba(255,255,255,0.03) 70px
  );
}
.page-header h1 {
  font-family: 'Orbitron', sans-serif;
  font-size: 2rem;
  font-weight: 900;
  background: linear-gradient(to right, #ffffff, #bfdbfe);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 0 10px rgba(0,166,192,0.3));
}
.page-header .role-badge {
  background: rgba(255,255,255,0.2);
  backdrop-filter: blur(10px);
  padding: 8px 22px;
  border-radius: 50px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* ── CARDS ── */
.card {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 28px;
  margin-bottom: 25px;
  box-shadow: 0 8px 30px rgba(29,78,216,0.06);
  border: 1px solid var(--border-subtle);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  animation: fadeInUp 0.5s ease-out;
}
.card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; width: 100%; height: 4px;
  background: linear-gradient(90deg, var(--accent-blue), var(--accent-cyan));
  transform: scaleX(0);
  transition: transform 0.4s ease;
}
.card:hover::before { transform: scaleX(1); }
.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(29,78,216,0.12);
}
.card h3 {
  font-family: 'Orbitron', sans-serif;
  font-size: 1.2rem;
  margin-bottom: 20px;
  color: #1e3a8a;
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 2px solid #e0e7ff;
  padding-bottom: 12px;
}

/* ── STATS GRID ── */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px,1fr));
  gap: 15px;
  margin-bottom: 20px;
}
.stat-box {
  background: linear-gradient(135deg, #f0f7ff, #d9e9ff);
  border-radius: 12px;
  padding: 22px 15px;
  text-align: center;
  border: 1px solid #bfdbfe;
  transition: all 0.3s ease;
}
.stat-box:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(29,78,216,0.15);
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
}
.stat-box .number { font-size: 2.2rem; font-weight: 800; color: var(--accent-blue); }
.stat-box .label { font-size: 0.8rem; color: #475569; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600; }

/* ── FORMS ── */
input, select, textarea {
  width: 100%;
  padding: 13px 16px;
  border: 1px solid #bfdbfe;
  border-radius: 10px;
  font-family: inherit;
  font-size: 0.95rem;
  transition: all 0.3s;
  background: #f0f7ff;
}
input:focus, select:focus, textarea:focus {
  outline: none;
  border-color: var(--accent-blue);
  box-shadow: 0 0 0 4px rgba(29,78,216,0.15);
  background: white;
}
textarea { resize: vertical; min-height: 100px; }

/* ── BUTTONS ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  position: relative;
  overflow: hidden;
}
.btn::after {
  content: '';
  position: absolute;
  top: 50%; left: 50%;
  width: 0; height: 0;
  background: rgba(255,255,255,0.4);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  transition: width 0.6s, height 0.6s;
}
.btn:active::after { width: 300px; height: 300px; }
.btn-primary { background: var(--accent-blue); color: white; }
.btn-primary:hover { background: #1e40af; transform: scale(1.03); }
.btn-success { background: var(--accent-green); color: white; }
.btn-success:hover { background: #047857; transform: scale(1.03); }
.btn-warning { background: var(--accent-gold); color: white; }
.btn-warning:hover { background: #92400e; transform: scale(1.03); }

/* ── ALERT ITEMS ── */
.alert-item {
  background: #f0f9ff;
  border-left: 5px solid var(--accent-gold);
  padding: 16px;
  margin-bottom: 12px;
  border-radius: 8px;
  transition: all 0.3s;
  animation: slideInLeft 0.4s ease-out;
}
.alert-item.critical {
  border-color: var(--accent-red);
  background: #fee2e2;
  animation: pulseWarning 2s infinite;
}
.alert-item:hover { transform: translateX(4px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

/* ── TABLE ── */
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { text-align: left; padding: 14px 12px; border-bottom: 1px solid #dbeafe; }
th { background: #f0f7ff; color: #1e3a8a; font-weight: 700; font-size: 0.85rem; }
tr { transition: background 0.2s; }
tr:hover { background: #f0f7ff; }

/* ── NOTIFICATION TOAST ── */
.notif-toast {
  position: fixed;
  top: 30px;
  right: 30px;
  background: var(--accent-green);
  color: white;
  padding: 16px 28px;
  border-radius: 12px;
  font-weight: 700;
  box-shadow: 0 15px 30px rgba(0,0,0,0.2);
  z-index: 9999;
  display: none;
  animation: slideInRight 0.4s ease-out;
  backdrop-filter: blur(6px);
}
.notif-toast.error { background: var(--accent-red); }

/* ── LOGIN PAGE ── */
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  min-height: 100vh;
  background: var(--bg-light);
}
.login-card {
  background: white;
  border-radius: 24px;
  padding: 45px;
  width: 420px;
  max-width: 90%;
  box-shadow: 0 30px 50px rgba(29,78,216,0.1);
  animation: fadeInUp 0.6s ease-out;
}
.login-card h2 { font-family: 'Orbitron',sans-serif; color: #1e3a8a; margin-bottom: 30px; text-align: center; }
.login-card a { color: var(--accent-blue); text-decoration: none; font-weight: 600; }

/* ── KEYFRAMES ── */
@keyframes fadeInUp { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0); } }
@keyframes fadeInDown { from { opacity:0; transform: translateY(-20px); } to { opacity:1; transform: translateY(0); } }
@keyframes slideInLeft { from { opacity:0; transform: translateX(-20px); } to { opacity:1; transform: translateX(0); } }
@keyframes slideInRight { from { opacity:0; transform: translateX(30px); } to { opacity:1; transform: translateX(0); } }
@keyframes fadeSlideRight { from { opacity:0; transform: translateX(-15px); } to { opacity:1; transform: translateX(0); } }
@keyframes subtlePulse {
  0% { box-shadow: 0 0 0 0 rgba(220,38,38,0.4); }
  70% { box-shadow: 0 0 0 8px rgba(220,38,38,0); }
  100% { box-shadow: 0 0 0 0 rgba(220,38,38,0); }
}
@keyframes pulseWarning {
  0% { background-color: #fee2e2; }
  50% { background-color: #fecaca; }
  100% { background-color: #fee2e2; }
}
@keyframes glitch1 { /* glitch effect can be applied to specific text if needed */ }
@keyframes glitch2 { /* glitch effect can be applied to specific text if needed */ }

/* Responsive */
@media (max-width: 768px) {
  .sidebar { width: 0; padding: 0; overflow: hidden; }
  .sidebar:hover { width: 0; }
  .main-content { margin-left: 0; width: 100%; }
  .page-header { flex-direction: column; align-items: flex-start; gap: 15px; }
}
</style>
</head>
<body>

<!-- Canvas for light particles -->
<canvas id="particle-canvas"></canvas>

<!-- Notifications -->
<div class="notif-toast" id="toast"></div>

<?php if (!$loggedIn): ?>
<!-- ========== LOGIN / REGISTER (light cyber) ========== -->
<div class="login-container">
  <div class="login-card">
    <h2 id="authTitle">🔐 Login</h2>
    <form id="authForm">
      <div class="form-group" id="nameGroup" style="display:none">
        <label>Full Name</label>
        <input type="text" id="full_name" placeholder="John Doe">
      </div>
      <br>
      <div class="form-group">
        <label>Email</label>
        <input type="email" id="email" required placeholder="you@company.com">
      </div>
      <br>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="password" required minlength="8" placeholder="••••••••">
      </div>
      <br>
      <button type="submit" class="btn btn-primary" style="width:100%">Login</button>
    </form>
    <p style="margin-top:20px; text-align:center">
      <a href="#" id="toggleAuth">Don't have an account? Register</a>
    </p>
  </div>
</div>
<?php else: ?>
<!-- ========== AUTHENTICATED DASHBOARD ========== -->
<div class="site-wrap">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo"><span>🛡️</span> CyberShield</div>
    <nav>
      <a href="#" class="active" onclick="return false;">📊 Dashboard</a>
      <a href="#" onclick="return false;">📋 Incidents</a>
      <a href="#" onclick="return false;">🔔 Alerts</a>
      <?php if ($user['role'] === 'admin'): ?>
      <a href="#" onclick="return false;">⚙️ Admin Panel</a>
      <?php endif; ?>
    </nav>
    <div class="user-info">
      <p>Logged in as</p>
      <strong><?= htmlspecialchars($user['full_name']) ?></strong>
      <p><?= ucfirst($user['role']) ?></p>
      <button class="logout-btn" id="logoutBtn">🚪 Logout</button>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1>Security Command Center</h1>
        <p>Real-time threat monitoring & incident management</p>
      </div>
      <div class="role-badge"><?= ucfirst($user['role']) ?></div>
    </div>

    <!-- Stats -->
    <div class="card">
      <h3>📈 Incident Overview</h3>
      <div id="statsContent">Loading statistics...</div>
    </div>

    <!-- Report Incident -->
    <div class="card">
      <h3>🚨 Report New Incident</h3>
      <form id="reportForm">
        <div class="form-group">
          <label>Incident Title</label>
          <input type="text" id="incTitle" required placeholder="e.g., Suspicious phishing email">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea id="incDesc" rows="4" required placeholder="Describe the incident in detail..."></textarea>
        </div>
        <button type="submit" class="btn btn-success">📤 Submit Report</button>
      </form>
    </div>

    <!-- Incidents Table -->
    <div class="card">
      <h3>📋 Recent Incidents</h3>
      <div id="incidentList">Loading incidents...</div>
    </div>

    <!-- Live Threat Alerts -->
    <div class="card">
      <h3>🔔 Live Threat Alerts</h3>
      <div id="alertPanel" style="max-height: 400px; overflow-y: auto;">Waiting for alerts...</div>
    </div>

    <!-- Admin Alert Broadcast -->
    <?php if ($user['role'] === 'admin'): ?>
    <div class="card" id="adminCard">
      <h3>⚡ Broadcast Global Alert</h3>
      <form id="alertForm">
        <div class="form-group">
          <label>Alert Title</label>
          <input type="text" id="alertTitle" required placeholder="Ransomware attack warning">
        </div>
        <div class="form-group">
          <label>Message</label>
          <textarea id="alertMsg" rows="3" required placeholder="Detailed advisory..."></textarea>
        </div>
        <div class="form-group">
          <label>Severity</label>
          <select id="alertSeverity">
            <option>Low</option><option>Medium</option><option selected>High</option><option>Critical</option>
          </select>
        </div>
        <button type="submit" class="btn btn-warning">📢 Broadcast Alert</button>
      </form>
    </div>
    <?php endif; ?>
  </main>
</div>
<?php endif; ?>

<!-- ═══════════ SCRIPTS (functionality unchanged) ═══════════ -->
<script>
const csrfToken = "<?= $csrfToken ?>";
const userRole = "<?= $user['role'] ?? '' ?>";
let lastAlertId = 0;

// Toast
function showToast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'notif-toast' + (type==='error'?' error':'');
  t.style.display = 'block';
  setTimeout(()=>t.style.display='none', 4000);
}

// AJAX helper
async function apiCall(action, data={}) {
  const stateActions = ['reportIncident','updateIncident','addComment','createAlert'];
  if (stateActions.includes(action)) { data.csrf_token = csrfToken; }
  try {
    const resp = await fetch('api.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action, data })
    });
    const res = await resp.json();
    if (res.status === 'error') throw new Error(res.message);
    return res;
  } catch(err) {
    showToast(err.message, 'error');
    throw err;
  }
}

// AUTH LOGIC (same as before)
<?php if(!$loggedIn): ?>
let isLogin=true;
document.getElementById('toggleAuth').addEventListener('click', e=>{
  e.preventDefault();
  isLogin=!isLogin;
  document.getElementById('authTitle').textContent = isLogin?'🔐 Login':'📝 Register';
  document.getElementById('nameGroup').style.display = isLogin?'none':'block';
  document.querySelector('#authForm button').textContent = isLogin?'Login':'Register';
});
document.getElementById('authForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const email=document.getElementById('email').value;
  const pass=document.getElementById('password').value;
  const name=document.getElementById('full_name')?.value||'';
  try{
    if(isLogin) await apiCall('login',{email,password:pass});
    else await apiCall('register',{full_name:name,email,password:pass});
    window.location.reload();
  }catch(err){}
});
<?php endif; ?>

<?php if($loggedIn): ?>
// Logout
document.getElementById('logoutBtn').addEventListener('click', async ()=>{
  await apiCall('logout');
  window.location.reload();
});

// Report incident
document.getElementById('reportForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const title=document.getElementById('incTitle').value.trim();
  const desc=document.getElementById('incDesc').value.trim();
  try{
    const res=await apiCall('reportIncident',{title,description:desc});
    showToast(`Incident #${res.data.incident_id} reported (${res.data.assigned_category})`);
    document.getElementById('reportForm').reset();
    loadIncidents(); loadStats();
  }catch(err){}
});

// Admin broadcast alert
<?php if($user['role']==='admin'): ?>
document.getElementById('alertForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const title=document.getElementById('alertTitle').value.trim();
  const msg=document.getElementById('alertMsg').value.trim();
  const severity=document.getElementById('alertSeverity').value;
  try{
    await apiCall('createAlert',{title,message:msg,severity});
    showToast('Alert broadcasted');
    document.getElementById('alertForm').reset();
  }catch(err){}
});
<?php endif; ?>

// Load stats
async function loadStats(){
  try{
    const res=await apiCall('getDashboardStats');
    const d=res.data;
    let html='<div class="stat-grid">';
    d.stats.forEach(s=>html+=`<div class="stat-box"><div class="number">${s.count}</div><div class="label">${s.status}</div></div>`);
    html+='</div>';
    if(d.recent_alerts.length){
      html+='<h4 style="margin-top:20px;color:#1e3a8a">Recent Alerts</h4>';
      d.recent_alerts.forEach(a=>html+=`<div class="alert-item"><strong>${a.title}</strong> (${a.severity})</div>`);
    }
    document.getElementById('statsContent').innerHTML=html;
  }catch(err){ document.getElementById('statsContent').textContent='Failed to load stats'; }
}

// Load incidents
async function loadIncidents(){
  try{
    const res=await apiCall('getIncidents');
    const incidents=res.data;
    if(!incidents.length){ document.getElementById('incidentList').innerHTML='<p>No incidents found.</p>'; return; }
    let html='<table><tr><th>ID</th><th>Title</th><th>Category</th><th>Severity</th><th>Status</th><th>Date</th></tr>';
    incidents.forEach(i=>{
      html+=`<tr>
        <td>#${i.id}</td><td>${i.title}</td><td>${i.assigned_category}</td>
        <td>${i.assigned_severity}</td>
        <td><span style="padding:4px 12px;border-radius:20px;background:#dbeafe;color:#1e40af;font-weight:700;">${i.status}</span></td>
        <td>${new Date(i.created_at).toLocaleString()}</td>
      </tr>`;
    });
    html+='</table>';
    document.getElementById('incidentList').innerHTML=html;
  }catch(err){ document.getElementById('incidentList').textContent='Error loading incidents'; }
}

// Polling alerts
async function pollAlerts(){
  try{
    const res=await apiCall('pollAlerts',{last_alert_id:lastAlertId});
    if(res.alerts.length>0){
      lastAlertId=Math.max(...res.alerts.map(a=>a.id));
      const panel=document.getElementById('alertPanel');
      res.alerts.forEach(a=>{
        const div=document.createElement('div');
        div.className='alert-item'+(a.severity==='Critical'?' critical':'');
        div.innerHTML=`<strong>${a.title}</strong> <span style="background:#e0e7ff;padding:2px 10px;border-radius:6px;font-size:0.75rem;margin-left:8px;">${a.severity}</span>
                       <br><small>${new Date(a.created_at).toLocaleString()}</small>
                       <p style="margin-top:6px;">${a.message}</p>`;
        panel.prepend(div);
      });
      showToast('New threat alert received');
    }
  }catch(e){/*ignore*/}
}

// Initial load
loadStats();
loadIncidents();
setInterval(pollAlerts, 4000);
setInterval(loadIncidents, 10000);
<?php endif; ?>

// ── LIGHT PARTICLES CANVAS ──
const canvas = document.getElementById('particle-canvas');
const ctx = canvas.getContext('2d');
function resizeCanvas(){ canvas.width=window.innerWidth; canvas.height=window.innerHeight; }
resizeCanvas();
window.addEventListener('resize',resizeCanvas);

let particlesArray = [];
const numberOfParticles = 50;
class Particle {
  constructor(){
    this.x = Math.random() * canvas.width;
    this.y = Math.random() * canvas.height;
    this.size = Math.random() * 3 + 1;
    this.speedX = Math.random() * 0.5 - 0.25;
    this.speedY = Math.random() * 0.5 - 0.25;
    this.color = Math.random() > 0.5 ? 'rgba(29,78,216,0.25)' : 'rgba(0,166,192,0.2)';
  }
  update(){
    this.x += this.speedX;
    this.y += this.speedY;
    if(this.x > canvas.width) this.x=0;
    if(this.x < 0) this.x=canvas.width;
    if(this.y > canvas.height) this.y=0;
    if(this.y < 0) this.y=canvas.height;
  }
  draw(){
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.size, 0, Math.PI*2);
    ctx.fillStyle = this.color;
    ctx.fill();
  }
}
function initParticles(){
  particlesArray = [];
  for(let i=0;i<numberOfParticles;i++) particlesArray.push(new Particle());
}
initParticles();
function animateParticles(){
  ctx.clearRect(0,0,canvas.width,canvas.height);
  for(let p of particlesArray){ p.update(); p.draw(); }
  // draw some connections
  for(let a=0;a<particlesArray.length;a++){
    for(let b=a+1;b<particlesArray.length;b++){
      let dx=particlesArray[a].x - particlesArray[b].x;
      let dy=particlesArray[a].y - particlesArray[b].y;
      let dist=Math.sqrt(dx*dx+dy*dy);
      if(dist<100){
        ctx.beginPath();
        ctx.strokeStyle = 'rgba(29,78,216,0.08)';
        ctx.lineWidth = 0.5;
        ctx.moveTo(particlesArray[a].x, particlesArray[a].y);
        ctx.lineTo(particlesArray[b].x, particlesArray[b].y);
        ctx.stroke();
      }
    }
  }
  requestAnimationFrame(animateParticles);
}
animateParticles();
</script>
</body>
</html>