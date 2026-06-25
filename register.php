<?php

session_start();
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = $_POST['role'] ?? 'pharmacist';

    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();
        // Check duplicate
        $chk = $db->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $chk->bind_param('ss', $email, $username);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'Email or username is already taken. Try a different one.';
        } else {
            $hash   = password_hash($password, PASSWORD_DEFAULT);
            $colors = ['#00d4aa','#845ef7','#f72585','#3b82f6','#ffbe0b'];
            $color  = $colors[array_rand($colors)];
            $stmt   = $db->prepare("INSERT INTO users (full_name, email, username, password, role, avatar_color) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('ssssss', $full_name, $email, $username, $hash, $role, $color);
            if ($stmt->execute()) {
                $success = "Account created! Redirecting to login…";
                header("Refresh: 2; url=login.php");
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PharmaCare — Create Account</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
  --teal:   #00d4aa;
  --teal2:  #00b4d8;
  --purple: #845ef7;
  --pink:   #f72585;
  --amber:  #ffbe0b;
  --bg:     #060b18;
  --text:   #eef2ff;
  --muted:  #8892a4;
  --font:   'Poppins', sans-serif;
}

@keyframes gradientBG {
  0%,100% { background-position: 0% 50%; }
  50%     { background-position: 100% 50%; }
}
@keyframes floatUp {
  0%   { transform: translateY(0) rotate(0deg);    opacity: 0; }
  10%  { opacity: .5; }
  90%  { opacity: .15; }
  100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
}
@keyframes slideInCard {
  from { opacity: 0; transform: translateY(40px) scale(.96); }
  to   { opacity: 1; transform: translateY(0)    scale(1); }
}
@keyframes fadeIn { from{opacity:0} to{opacity:1} }
@keyframes pulseRing {
  0%  { transform:scale(1);   opacity:.6; }
  100%{ transform:scale(1.8); opacity:0; }
}
@keyframes shimmerLine {
  0%  { left:-100%; }
  100%{ left: 200%; }
}
@keyframes spin   { to{ transform:rotate(360deg); } }
@keyframes errShake {
  0%,100%{transform:translateX(0)}
  25%    {transform:translateX(-8px)}
  75%    {transform:translateX(8px)}
}
@keyframes successBounce {
  0%  {transform:scale(0) rotate(-10deg); opacity:0;}
  70% {transform:scale(1.1) rotate(3deg);}
  100%{transform:scale(1) rotate(0deg);  opacity:1;}
}
@keyframes progressFill {
  from{width:0}
  to{width:var(--pw,0%)}
}

*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

body {
  font-family: var(--font);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg);
  overflow-x: hidden;
  position: relative;
  padding: 24px;
}

/* BG */
.bg-gradient {
  position:fixed; inset:0;
  background: linear-gradient(-45deg,#060b18,#0d1b3e,#0a1628,#060b18,#12052a,#06111f);
  background-size: 400% 400%;
  animation: gradientBG 12s ease infinite;
  z-index:0;
}
.orb {
  position:fixed; border-radius:50%;
  filter:blur(90px); z-index:0; pointer-events:none;
}
.orb-1{width:500px;height:500px;background:radial-gradient(circle,rgba(0,212,170,.16),transparent 70%);top:-150px;right:-100px;}
.orb-2{width:400px;height:400px;background:radial-gradient(circle,rgba(132,94,247,.18),transparent 70%);bottom:-80px;left:-80px;}
.orb-3{width:280px;height:280px;background:radial-gradient(circle,rgba(247,37,133,.12),transparent 70%);top:40%;left:20%;}
.grid-overlay {
  position:fixed;inset:0;z-index:0;pointer-events:none;
  background-image:
    linear-gradient(rgba(0,212,170,.025) 1px,transparent 1px),
    linear-gradient(90deg,rgba(0,212,170,.025) 1px,transparent 1px);
  background-size:60px 60px;
}
.particles{position:fixed;inset:0;z-index:0;pointer-events:none;}
.particle{position:absolute;bottom:-20px;border-radius:50%;animation:floatUp linear infinite;}

/* CARD */
.auth-wrapper {
  position:relative;z-index:10;
  width:100%;
  display:flex;
  align-items:flex-start;
  justify-content:center;
  gap:0;
  min-height:100vh;
  padding:40px 24px;
}

.reg-card {
  width:520px;
  background:rgba(255,255,255,.04);
  border:1px solid rgba(255,255,255,.08);
  border-radius:28px;
  padding:44px;
  backdrop-filter:blur(24px);
  -webkit-backdrop-filter:blur(24px);
  position:relative;
  overflow:hidden;
  animation:slideInCard .7s cubic-bezier(.34,1.56,.64,1) both .1s;
  box-shadow:
    0 0 0 1px rgba(132,94,247,.06),
    0 24px 80px rgba(0,0,0,.5),
    inset 0 1px 0 rgba(255,255,255,.06);
}
.reg-card::before {
  content:'';
  position:absolute;top:0;left:-100%;
  width:60%;height:2px;
  background:linear-gradient(90deg,transparent,var(--purple),var(--teal),transparent);
  animation:shimmerLine 3s ease infinite 1s;
}
.reg-card::after {
  content:'';position:absolute;top:0;left:0;
  width:100%;height:100%;
  background:radial-gradient(circle at top left,rgba(132,94,247,.07),transparent 60%);
  pointer-events:none;
}

/* Header */
.card-head {text-align:center;margin-bottom:32px;}
.card-icon-wrap {
  width:64px;height:64px;
  background:linear-gradient(135deg,rgba(132,94,247,.2),rgba(247,37,133,.1));
  border:1px solid rgba(132,94,247,.3);
  border-radius:20px;
  display:flex;align-items:center;justify-content:center;
  font-size:28px;margin:0 auto 16px;position:relative;
}
.card-icon-wrap::before {
  content:'';position:absolute;inset:-6px;
  border-radius:26px;
  border:1px solid rgba(132,94,247,.15);
  animation:pulseRing 2.5s ease infinite;
}
.card-title {font-size:24px;font-weight:800;color:var(--text);margin-bottom:6px;letter-spacing:-.3px;}
.card-sub   {font-size:13px;color:var(--muted);}

/* Alert */
.auth-alert {
  padding:12px 16px;border-radius:12px;
  font-size:13px;font-weight:500;
  margin-bottom:20px;
  display:flex;align-items:center;gap:10px;
}
.alert-error   {background:rgba(247,37,133,.12);border:1px solid rgba(247,37,133,.25);color:#ff82b2;animation:errShake .5s ease;}
.alert-success {background:rgba(0,212,170,.12); border:1px solid rgba(0,212,170,.25); color:var(--teal);animation:successBounce .5s ease;}

/* 2-col grid */
.form-grid {
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:16px;
  margin-bottom:0;
}
.form-group {
  display:flex;flex-direction:column;gap:6px;
  margin-bottom:16px;
}
.full {grid-column:1/-1;}

label {
  font-size:11px;font-weight:600;
  color:var(--muted);
  text-transform:uppercase;letter-spacing:1px;
}
.input-wrap {position:relative;display:flex;align-items:center;}
.input-icon {
  position:absolute;left:14px;
  font-size:16px;pointer-events:none;opacity:.4;transition:opacity .2s;
}
.auth-input {
  width:100%;
  padding:12px 14px 12px 44px;
  background:rgba(255,255,255,.05);
  border:1.5px solid rgba(255,255,255,.09);
  border-radius:12px;
  font-family:var(--font);
  font-size:13.5px;color:var(--text);
  outline:none;transition:all .2s ease;
}
.auth-input::placeholder{color:rgba(255,255,255,.18);}
.auth-input:focus {
  border-color:var(--purple);
  background:rgba(132,94,247,.06);
  box-shadow:0 0 0 4px rgba(132,94,247,.1);
}
.auth-input:focus ~ .input-icon,
.input-wrap:focus-within .input-icon{opacity:1;}
select.auth-input{appearance:none;cursor:pointer;padding-right:40px;}
.select-arrow {
  position:absolute;right:14px;
  font-size:11px;color:var(--muted);pointer-events:none;
}

/* Password strength */
.pwd-hint{font-size:11px;color:var(--muted);margin-top:4px;}
.strength-bar {
  height:4px;border-radius:2px;
  background:rgba(255,255,255,.07);
  margin-top:8px;overflow:hidden;
}
.strength-fill {
  height:100%;border-radius:2px;
  width:0%;transition:width .3s ease,background .3s ease;
}

/* Password toggle */
.pwd-toggle {
  position:absolute;right:12px;
  background:none;border:none;
  color:var(--muted);cursor:pointer;font-size:15px;
  transition:color .2s;padding:4px;
}
.pwd-toggle:hover{color:var(--purple);}

/* Role cards */
.role-options {
  display:grid;grid-template-columns:repeat(3,1fr);gap:10px;
}
.role-option {cursor:pointer;}
.role-option input[type=radio]{display:none;}
.role-label {
  display:flex;flex-direction:column;align-items:center;gap:6px;
  padding:12px 8px;
  border:1.5px solid rgba(255,255,255,.08);
  border-radius:12px;
  font-size:12px;font-weight:600;color:var(--muted);
  transition:all .2s ease;text-align:center;cursor:pointer;
}
.role-label:hover{border-color:rgba(132,94,247,.3);color:var(--text);}
.role-option input:checked + .role-label {
  border-color:var(--purple);
  background:rgba(132,94,247,.12);
  color:var(--purple);
  box-shadow:0 0 16px rgba(132,94,247,.2);
}
.role-emoji{font-size:22px;}

/* Submit */
.btn-submit {
  width:100%;padding:14px;
  background:linear-gradient(135deg,var(--purple),var(--pink));
  border:none;border-radius:14px;
  font-family:var(--font);font-size:15px;font-weight:700;
  color:#fff;cursor:pointer;
  position:relative;overflow:hidden;
  transition:all .25s ease;margin-top:4px;
  letter-spacing:.3px;
  box-shadow:0 6px 24px rgba(132,94,247,.4);
}
.btn-submit::before {
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(255,255,255,.15),transparent);
  opacity:0;transition:opacity .2s;
}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 12px 36px rgba(132,94,247,.55);}
.btn-submit:hover::before{opacity:1;}

.spinner {
  display:none;width:20px;height:20px;
  border:2px solid rgba(255,255,255,.3);
  border-top-color:#fff;
  border-radius:50%;animation:spin .7s linear infinite;
  margin:0 auto;
}

.or-divider {display:flex;align-items:center;gap:12px;margin:20px 0;}
.or-line{flex:1;height:1px;background:rgba(255,255,255,.07);}
.or-text{font-size:12px;color:var(--muted);white-space:nowrap;}

.switch-link{text-align:center;font-size:13px;color:var(--muted);}
.switch-link a{
  color:var(--teal);font-weight:600;text-decoration:none;transition:color .2s;
  position:relative;
}
.switch-link a::after{
  content:'';position:absolute;bottom:-2px;left:0;right:0;
  height:1px;background:var(--teal);transform:scaleX(0);transition:transform .2s;
}
.switch-link a:hover::after{transform:scaleX(1);}

/* Logo top */
.top-logo {
  display:flex;align-items:center;gap:12px;margin-bottom:32px;justify-content:center;
}
.logo-icon {
  width:44px;height:44px;
  background:linear-gradient(135deg,var(--purple),var(--pink));
  border-radius:13px;display:flex;align-items:center;justify-content:center;
  font-size:22px;box-shadow:0 0 24px rgba(132,94,247,.4);
}
.logo-text-sm {
  font-size:20px;font-weight:900;
  background:linear-gradient(135deg,var(--purple),var(--pink));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}

@media(max-width:560px){
  .form-grid{grid-template-columns:1fr;}
  .reg-card{padding:28px 20px;}
  .role-options{grid-template-columns:repeat(3,1fr);}
}
</style>
</head>
<body>

<div class="bg-gradient"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<div class="grid-overlay"></div>
<div class="particles" id="particles"></div>

<div class="auth-wrapper">
<div class="reg-card">

  <!-- Logo -->
  <div class="top-logo">
    <div class="logo-icon">⚕</div>
    <div class="logo-text-sm">PharmaCare</div>
  </div>

  <!-- Header -->
  <div class="card-head">
    <div class="card-icon-wrap">✨</div>
    <div class="card-title">Create Account</div>
    <div class="card-sub">Join PharmaCare — your pharmacy, your control</div>
  </div>

  <?php if ($error): ?>
  <div class="auth-alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
  <div class="auth-alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" id="regForm">

    <!-- Row 1 -->
    <div class="form-grid">
      <div class="form-group full">
        <label>Full Name</label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <input type="text" name="full_name" class="auth-input"
                 placeholder="e.g. Ali Hassan" required
                 value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label>Username</label>
        <div class="input-wrap">
          <span class="input-icon">🏷</span>
          <input type="text" name="username" class="auth-input"
                 placeholder="e.g. ali_pharma" required
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label>Email Address</label>
        <div class="input-wrap">
          <span class="input-icon">📧</span>
          <input type="email" name="email" class="auth-input"
                 placeholder="ali@pharmacy.com" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label>Password</label>
        <div class="input-wrap">
          <span class="input-icon">🔑</span>
          <input type="password" name="password" id="pwdInput" class="auth-input"
                 placeholder="Min. 6 characters" required oninput="checkStrength(this.value)">
          <button type="button" class="pwd-toggle" onclick="togglePwd('pwdInput','pwdBtn1')" id="pwdBtn1">👁</button>
        </div>
        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        <div class="pwd-hint" id="strengthText">Enter a password</div>
      </div>

      <div class="form-group">
        <label>Confirm Password</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input type="password" name="confirm_password" id="confirmPwd" class="auth-input"
                 placeholder="Repeat password" required>
          <button type="button" class="pwd-toggle" onclick="togglePwd('confirmPwd','pwdBtn2')" id="pwdBtn2">👁</button>
        </div>
      </div>
    </div>

    <!-- Role selector -->
    <div class="form-group" style="margin-bottom:20px">
      <label>Select Role</label>
      <div class="role-options">
        <label class="role-option">
          <input type="radio" name="role" value="admin" <?= ($_POST['role']??'')=='admin'?'checked':'' ?>>
          <div class="role-label"><span class="role-emoji">👑</span>Admin</div>
        </label>
        <label class="role-option">
          <input type="radio" name="role" value="pharmacist" <?= (($_POST['role']??'pharmacist')=='pharmacist')?'checked':'' ?>>
          <div class="role-label"><span class="role-emoji">⚕</span>Pharmacist</div>
        </label>
        <label class="role-option">
          <input type="radio" name="role" value="cashier" <?= ($_POST['role']??'')=='cashier'?'checked':'' ?>>
          <div class="role-label"><span class="role-emoji">💰</span>Cashier</div>
        </label>
      </div>
    </div>

    <button type="submit" class="btn-submit" id="submitBtn">
      <span id="btnText">Create Account →</span>
      <div class="spinner" id="spinner"></div>
    </button>
  </form>

  <div class="or-divider">
    <div class="or-line"></div>
    <span class="or-text">Already have an account?</span>
    <div class="or-line"></div>
  </div>
  <div class="switch-link">
    <a href="login.php">← Sign in here</a>
  </div>

</div>
</div>

<script>
// Particles
const c = document.getElementById('particles');
const cols = ['#00d4aa','#845ef7','#f72585','#ffbe0b','#00b4d8'];
for (let i=0;i<25;i++){
  const p = document.createElement('div');
  p.className = 'particle';
  const s = Math.random()*5+2;
  p.style.cssText=`left:${Math.random()*100}%;width:${s}px;height:${s}px;background:${cols[Math.floor(Math.random()*cols.length)]};animation-duration:${Math.random()*12+8}s;animation-delay:${Math.random()*8}s;`;
  c.appendChild(p);
}

// Password toggle
function togglePwd(inputId, btnId) {
  const inp = document.getElementById(inputId);
  const btn = document.getElementById(btnId);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}

// Password strength
function checkStrength(val) {
  const fill = document.getElementById('strengthFill');
  const text = document.getElementById('strengthText');
  let score = 0;
  if (val.length >= 6)  score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const map = [
    {w:'0%',   bg:'transparent', t:'Enter a password'},
    {w:'20%',  bg:'#f72585',     t:'Too weak'},
    {w:'40%',  bg:'#ff6b6b',     t:'Weak'},
    {w:'60%',  bg:'#ffbe0b',     t:'Fair'},
    {w:'80%',  bg:'#00b4d8',     t:'Good'},
    {w:'100%', bg:'#00d4aa',     t:'Strong 💪'},
  ];
  const m = map[score] || map[0];
  fill.style.width = m.w;
  fill.style.background = m.bg;
  text.textContent = m.t;
  text.style.color = m.bg;
}

// Spinner on submit
document.getElementById('regForm').addEventListener('submit', function(e) {
  const pwd = document.getElementById('pwdInput').value;
  const cfm = document.getElementById('confirmPwd').value;
  if (pwd !== cfm) return; // let PHP handle
  document.getElementById('btnText').style.display = 'none';
  document.getElementById('spinner').style.display = 'block';
});
</script>
</body>
</html>
