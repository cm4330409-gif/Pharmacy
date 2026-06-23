<?php
session_start();
require_once 'includes/db.php';

// Already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php'); exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND status='active' LIMIT 1");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username']  = $user['username'];

            // Update last login
            $db->query("UPDATE users SET last_login=NOW() WHERE id={$user['id']}");

            header('Location: index.php'); exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PharmaCare — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════
   ROOT VARIABLES
══════════════════════════════════════ */
:root {
  --teal:    #00d4aa;
  --teal2:   #00b4d8;
  --purple:  #845ef7;
  --pink:    #f72585;
  --amber:   #ffbe0b;
  --bg:      #060b18;
  --card:    rgba(255,255,255,.04);
  --border:  rgba(255,255,255,.09);
  --text:    #eef2ff;
  --muted:   #8892a4;
  --font:    'Poppins', sans-serif;
}

/* ══════════════════════════════════════
   ANIMATIONS
══════════════════════════════════════ */
@keyframes gradientBG {
  0%   { background-position: 0% 50%; }
  50%  { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
@keyframes floatUp {
  0%   { transform: translateY(0) rotate(0deg);   opacity: 0; }
  10%  { opacity: .6; }
  90%  { opacity: .2; }
  100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
}
@keyframes slideInCard {
  from { opacity: 0; transform: translateY(40px) scale(.96); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}
@keyframes pulseRing {
  0%   { transform: scale(1);   opacity: .6; }
  100% { transform: scale(1.8); opacity: 0; }
}
@keyframes shimmerLine {
  0%   { left: -100%; }
  100% { left: 200%; }
}
@keyframes errorShake {
  0%,100% { transform: translateX(0); }
  20%,60% { transform: translateX(-8px); }
  40%,80% { transform: translateX(8px); }
}
@keyframes rotateOrb {
  from { transform: rotate(0deg) translateX(180px) rotate(0deg); }
  to   { transform: rotate(360deg) translateX(180px) rotate(-360deg); }
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
@keyframes successPop {
  0%  { transform: scale(0); opacity: 0; }
  70% { transform: scale(1.15); }
  100%{ transform: scale(1); opacity: 1; }
}

/* ══════════════════════════════════════
   BACKGROUND
══════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: var(--font);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: var(--bg);
  position: relative;
}

/* Animated multi-color gradient bg */
.bg-gradient {
  position: fixed;
  inset: 0;
  background: linear-gradient(-45deg, #060b18, #0d1b3e, #0a1628, #060b18, #12052a, #06111f);
  background-size: 400% 400%;
  animation: gradientBG 12s ease infinite;
  z-index: 0;
}

/* Glowing orbs */
.orb {
  position: fixed;
  border-radius: 50%;
  filter: blur(80px);
  z-index: 0;
  pointer-events: none;
}
.orb-1 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(0,212,170,.18), transparent 70%);
  top: -150px; left: -100px;
  animation: gradientBG 8s ease infinite;
}
.orb-2 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(132,94,247,.2), transparent 70%);
  bottom: -100px; right: -50px;
  animation: gradientBG 10s ease infinite reverse;
}
.orb-3 {
  width: 300px; height: 300px;
  background: radial-gradient(circle, rgba(247,37,133,.12), transparent 70%);
  top: 50%; right: 20%;
  animation: gradientBG 14s ease infinite;
}

/* Floating particles */
.particles {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}
.particle {
  position: absolute;
  bottom: -20px;
  border-radius: 50%;
  animation: floatUp linear infinite;
}

/* Grid overlay */
.grid-overlay {
  position: fixed;
  inset: 0;
  z-index: 0;
  background-image:
    linear-gradient(rgba(0,212,170,.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0,212,170,.03) 1px, transparent 1px);
  background-size: 60px 60px;
  pointer-events: none;
}

/* ══════════════════════════════════════
   LAYOUT
══════════════════════════════════════ */
.auth-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  width: 100%;
  padding: 24px;
  position: relative;
  z-index: 10;
}

/* ══════════════════════════════════════
   LEFT PANEL (brand side)
══════════════════════════════════════ */
.brand-panel {
  width: 420px;
  padding: 60px 50px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  animation: fadeIn 1s ease;
}

.brand-logo-wrap {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 48px;
}
.logo-icon {
  width: 56px; height: 56px;
  background: linear-gradient(135deg, var(--teal), var(--teal2));
  border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px;
  box-shadow: 0 0 30px rgba(0,212,170,.4), 0 0 60px rgba(0,212,170,.15);
  position: relative;
}
.logo-icon::after {
  content: '';
  position: absolute;
  inset: -3px;
  border-radius: 19px;
  background: linear-gradient(135deg, var(--teal), var(--purple), var(--pink));
  z-index: -1;
  opacity: .5;
  animation: spin 4s linear infinite;
  background-size: 200% 200%;
  animation: gradientBG 3s ease infinite;
}
.logo-text {
  font-size: 26px;
  font-weight: 900;
  background: linear-gradient(135deg, var(--teal), var(--teal2));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -.5px;
}

.brand-headline {
  font-size: 42px;
  font-weight: 900;
  line-height: 1.15;
  color: var(--text);
  margin-bottom: 18px;
  letter-spacing: -1px;
}
.brand-headline span {
  background: linear-gradient(135deg, var(--teal), var(--teal2), var(--purple));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  background-size: 200% auto;
  animation: gradientBG 4s ease infinite;
}
.brand-desc {
  font-size: 15px;
  color: var(--muted);
  line-height: 1.7;
  margin-bottom: 48px;
}

.feature-list { display: flex; flex-direction: column; gap: 16px; }
.feature-item {
  display: flex;
  align-items: center;
  gap: 14px;
  animation: fadeIn 1s ease both;
}
.feature-item:nth-child(1) { animation-delay: .2s; }
.feature-item:nth-child(2) { animation-delay: .4s; }
.feature-item:nth-child(3) { animation-delay: .6s; }
.feature-item:nth-child(4) { animation-delay: .8s; }

.feature-icon {
  width: 40px; height: 40px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}
.fi-1 { background: linear-gradient(135deg, rgba(0,212,170,.2), rgba(0,212,170,.05)); border: 1px solid rgba(0,212,170,.2); }
.fi-2 { background: linear-gradient(135deg, rgba(132,94,247,.2), rgba(132,94,247,.05)); border: 1px solid rgba(132,94,247,.2); }
.fi-3 { background: linear-gradient(135deg, rgba(247,37,133,.2), rgba(247,37,133,.05)); border: 1px solid rgba(247,37,133,.2); }
.fi-4 { background: linear-gradient(135deg, rgba(255,190,11,.2), rgba(255,190,11,.05)); border: 1px solid rgba(255,190,11,.2); }

.feature-text strong {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 2px;
}
.feature-text span { font-size: 12px; color: var(--muted); }

/* Divider */
.divider-v {
  width: 1px;
  height: 480px;
  background: linear-gradient(180deg, transparent, rgba(0,212,170,.3), rgba(132,94,247,.3), transparent);
  margin: 0 20px;
  flex-shrink: 0;
}

/* ══════════════════════════════════════
   CARD FORM
══════════════════════════════════════ */
.auth-card {
  width: 420px;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 28px;
  padding: 44px 44px 40px;
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  position: relative;
  overflow: hidden;
  animation: slideInCard .7s cubic-bezier(.34,1.56,.64,1) both .1s;
  box-shadow:
    0 0 0 1px rgba(0,212,170,.06),
    0 24px 80px rgba(0,0,0,.5),
    inset 0 1px 0 rgba(255,255,255,.06);
}

/* Top shimmer line */
.auth-card::before {
  content: '';
  position: absolute;
  top: 0; left: -100%;
  width: 60%; height: 2px;
  background: linear-gradient(90deg, transparent, var(--teal), var(--purple), transparent);
  animation: shimmerLine 3s ease infinite 1s;
}

/* Corner accent */
.auth-card::after {
  content: '';
  position: absolute;
  top: 0; right: 0;
  width: 120px; height: 120px;
  background: radial-gradient(circle at top right, rgba(0,212,170,.12), transparent 70%);
  pointer-events: none;
}

.card-header-text {
  text-align: center;
  margin-bottom: 36px;
}
.card-icon {
  width: 64px; height: 64px;
  background: linear-gradient(135deg, rgba(0,212,170,.2), rgba(0,180,216,.1));
  border: 1px solid rgba(0,212,170,.25);
  border-radius: 20px;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px;
  margin: 0 auto 16px;
  position: relative;
}
.card-icon::before {
  content: '';
  position: absolute;
  inset: -6px;
  border-radius: 26px;
  border: 1px solid rgba(0,212,170,.15);
  animation: pulseRing 2.5s ease infinite;
}
.card-title-text {
  font-size: 24px;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 6px;
  letter-spacing: -.3px;
}
.card-subtitle {
  font-size: 13px;
  color: var(--muted);
  line-height: 1.5;
}

/* Form elements */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 7px;
  margin-bottom: 18px;
}
.form-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 1px;
}
.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.input-icon {
  position: absolute;
  left: 14px;
  font-size: 17px;
  pointer-events: none;
  opacity: .5;
  transition: opacity .2s;
}
.auth-input {
  width: 100%;
  padding: 13px 14px 13px 44px;
  background: rgba(255,255,255,.05);
  border: 1.5px solid rgba(255,255,255,.09);
  border-radius: 14px;
  font-family: var(--font);
  font-size: 14px;
  color: var(--text);
  outline: none;
  transition: all .25s ease;
}
.auth-input::placeholder { color: rgba(255,255,255,.2); }
.auth-input:focus {
  border-color: var(--teal);
  background: rgba(0,212,170,.06);
  box-shadow: 0 0 0 4px rgba(0,212,170,.1);
}
.auth-input:focus + .input-icon,
.input-wrap:focus-within .input-icon { opacity: 1; }

/* Password toggle */
.pwd-toggle {
  position: absolute;
  right: 14px;
  background: none;
  border: none;
  color: var(--muted);
  cursor: pointer;
  font-size: 16px;
  padding: 4px;
  transition: color .2s;
}
.pwd-toggle:hover { color: var(--teal); }

/* Role selector */
.role-select {
  appearance: none;
  padding-right: 44px;
  cursor: pointer;
}

/* Alert */
.auth-alert {
  padding: 12px 16px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  animation: errorShake .5s ease;
}
.alert-error   { background: rgba(247,37,133,.12); border: 1px solid rgba(247,37,133,.25); color: #ff82b2; }
.alert-success { background: rgba(0,212,170,.12);  border: 1px solid rgba(0,212,170,.25);  color: var(--teal); animation: successPop .4s ease !important; }

/* Submit button */
.btn-submit {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, var(--teal), var(--teal2));
  border: none;
  border-radius: 14px;
  font-family: var(--font);
  font-size: 15px;
  font-weight: 700;
  color: #060b18;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: all .25s ease;
  margin-top: 8px;
  letter-spacing: .3px;
  box-shadow: 0 6px 24px rgba(0,212,170,.35);
}
.btn-submit::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,.15), transparent);
  opacity: 0;
  transition: opacity .25s;
}
.btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 36px rgba(0,212,170,.5);
}
.btn-submit:hover::before { opacity: 1; }
.btn-submit:active { transform: translateY(0); }

/* Divider */
.or-divider {
  display: flex;
  align-items: center;
  gap: 14px;
  margin: 22px 0;
}
.or-line { flex: 1; height: 1px; background: rgba(255,255,255,.08); }
.or-text { font-size: 12px; color: var(--muted); font-weight: 500; }

/* Switch link */
.switch-link {
  text-align: center;
  font-size: 13px;
  color: var(--muted);
  margin-top: 20px;
}
.switch-link a {
  color: var(--teal);
  font-weight: 600;
  text-decoration: none;
  transition: color .2s;
  position: relative;
}
.switch-link a::after {
  content: '';
  position: absolute;
  bottom: -2px; left: 0; right: 0;
  height: 1px;
  background: var(--teal);
  transform: scaleX(0);
  transition: transform .2s;
}
.switch-link a:hover::after { transform: scaleX(1); }

/* ══════════════════════════════════════
   SPINNER
══════════════════════════════════════ */
.spinner {
  display: none;
  width: 20px; height: 20px;
  border: 2px solid rgba(0,0,0,.2);
  border-top-color: #060b18;
  border-radius: 50%;
  animation: spin .7s linear infinite;
  margin: 0 auto;
}

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */
@media (max-width: 900px) {
  .brand-panel, .divider-v { display: none; }
}
@media (max-width: 480px) {
  .auth-card { padding: 32px 24px; border-radius: 20px; }
}
</style>
</head>
<body>

<!-- Backgrounds -->
<div class="bg-gradient"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<div class="grid-overlay"></div>
<div class="particles" id="particles"></div>

<div class="auth-wrapper">

  <!-- LEFT: Brand Panel -->
  <div class="brand-panel">
    <div class="brand-logo-wrap">
      <div class="logo-icon">⚕</div>
      <div class="logo-text">PharmaCare</div>
    </div>

    <h1 class="brand-headline">
      Smart Pharmacy<br><span>Management</span><br>System
    </h1>
    <p class="brand-desc">
      Complete pharmacy solution — track medicines, monitor expiry dates, manage stock levels, and generate daily sales reports in real time.
    </p>

    <div class="feature-list">
      <div class="feature-item">
        <div class="feature-icon fi-1">💊</div>
        <div class="feature-text">
          <strong>Medicine Inventory</strong>
          <span>Real-time stock tracking with low-level alerts</span>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon fi-2">📅</div>
        <div class="feature-text">
          <strong>Expiry Date Monitor</strong>
          <span>Automated alerts before medicines expire</span>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon fi-3">🧾</div>
        <div class="feature-text">
          <strong>Daily Sales Reports</strong>
          <span>Revenue analytics and transaction history</span>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon fi-4">🔒</div>
        <div class="feature-text">
          <strong>Role-Based Access</strong>
          <span>Admin, Pharmacist and Cashier roles</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Vertical divider -->
  <div class="divider-v"></div>

  <!-- RIGHT: Login Card -->
  <div class="auth-card">
    <div class="card-header-text">
      <div class="card-icon">🔐</div>
      <div class="card-title-text">Welcome Back</div>
      <div class="card-subtitle">Sign in to your PharmaCare account</div>
    </div>

    <?php if ($error): ?>
    <div class="auth-alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="loginForm">
      <div class="form-group">
        <label class="form-label">Username or Email</label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <input type="text" name="username" class="auth-input" placeholder="Enter username or email"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-wrap">
          <span class="input-icon">🔑</span>
          <input type="password" name="password" id="pwdInput" class="auth-input"
                 placeholder="Enter your password" required autocomplete="current-password">
          <button type="button" class="pwd-toggle" onclick="togglePwd()" id="pwdBtn">👁</button>
        </div>
      </div>

      <button type="submit" class="btn-submit" id="submitBtn">
        <span id="btnText">Sign In →</span>
        <div class="spinner" id="spinner"></div>
      </button>
    </form>

    <div class="or-divider">
      <div class="or-line"></div>
      <span class="or-text">Don't have an account?</span>
      <div class="or-line"></div>
    </div>

    <div class="switch-link">
      New user? <a href="register.php">Create an account</a>
    </div>

    <div style="text-align:center;margin-top:24px;padding-top:20px;border-top:1px solid rgba(255,255,255,.06)">
      <div style="font-size:11px;color:rgba(255,255,255,.2)">Default: admin / password</div>
    </div>
  </div>
</div>

<script>
// ── Floating particles
const container = document.getElementById('particles');
const colors = ['#00d4aa','#845ef7','#f72585','#ffbe0b','#00b4d8'];
for (let i = 0; i < 30; i++) {
  const p = document.createElement('div');
  p.className = 'particle';
  const size = Math.random() * 5 + 2;
  p.style.cssText = `
    left:${Math.random()*100}%;
    width:${size}px; height:${size}px;
    background:${colors[Math.floor(Math.random()*colors.length)]};
    animation-duration:${Math.random()*12+8}s;
    animation-delay:${Math.random()*8}s;
    opacity:.5;
  `;
  container.appendChild(p);
}

// ── Password toggle
function togglePwd() {
  const inp = document.getElementById('pwdInput');
  const btn = document.getElementById('pwdBtn');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}

// ── Submit spinner
document.getElementById('loginForm').addEventListener('submit', function() {
  document.getElementById('btnText').style.display = 'none';
  document.getElementById('spinner').style.display = 'block';
});
</script>
</body>
</html>
