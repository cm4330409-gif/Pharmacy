<?php
session_start();
require_once __DIR__ . '/db.php';

function getExpiryAlertCount() {
    $db = getDB();
    $r = $db->query("SELECT COUNT(*) as c FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND expiry_date >= CURDATE() AND status='active'");
    return $r->fetch_assoc()['c'];
}
function getLowStockCount() {
    $db = getDB();
    $r = $db->query("SELECT COUNT(*) as c FROM medicines WHERE stock_quantity <= min_stock_level AND status='active'");
    return $r->fetch_assoc()['c'];
}
function getExpiredCount() {
    $db = getDB();
    $r = $db->query("SELECT COUNT(*) as c FROM medicines WHERE expiry_date < CURDATE() AND status='active'");
    return $r->fetch_assoc()['c'];
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$expiryAlerts = getExpiryAlertCount();
$lowStockAlerts = getLowStockCount();
$expiredCount = getExpiredCount();
$totalAlerts = $expiryAlerts + $lowStockAlerts + $expiredCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PharmaCare — <?= ucfirst($currentPage) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">⚕</div>
    <div>
      <div class="brand-name">PharmaCare</div>
      <div class="brand-sub">Management System</div>
    </div>
  </div>

  <ul class="nav-links">
    <li><a href="index.php" class="<?= $currentPage==='index'?'active':'' ?>">
      <span class="nav-icon">◈</span> Dashboard
    </a></li>
    <li><a href="medicines.php" class="<?= $currentPage==='medicines'?'active':'' ?>">
      <span class="nav-icon">◉</span> Medicines
    </a></li>
    <li><a href="stock.php" class="<?= $currentPage==='stock'?'active':'' ?>">
      <span class="nav-icon">◫</span> Stock
      <?php if($lowStockAlerts > 0): ?><span class="badge-warn"><?= $lowStockAlerts ?></span><?php endif; ?>
    </a></li>
    <li><a href="expiry.php" class="<?= $currentPage==='expiry'?'active':'' ?>">
      <span class="nav-icon">◷</span> Expiry Tracker
      <?php if(($expiryAlerts+$expiredCount) > 0): ?><span class="badge-danger"><?= $expiryAlerts+$expiredCount ?></span><?php endif; ?>
    </a></li>
    <li><a href="sales.php" class="<?= $currentPage==='sales'?'active':'' ?>">
      <span class="nav-icon">◈</span> Sales
    </a></li>
    <li><a href="new_sale.php" class="<?= $currentPage==='new_sale'?'active':'' ?>">
      <span class="nav-icon">⊕</span> New Sale
    </a></li>
    <li><a href="reports.php" class="<?= $currentPage==='reports'?'active':'' ?>">
      <span class="nav-icon">◎</span> Reports
    </a></li>
    <li><a href="suppliers.php" class="<?= $currentPage==='suppliers'?'active':'' ?>">
      <span class="nav-icon">◉</span> Suppliers
    </a></li>
    <li><a href="categories.php" class="<?= $currentPage==='categories'?'active':'' ?>">
      <span class="nav-icon">◫</span> Categories
    </a></li>
  </ul>

  <div class="sidebar-footer">
    <?php if($totalAlerts > 0): ?>
    <div class="alert-pill">⚠ <?= $totalAlerts ?> alert<?= $totalAlerts>1?'s':'' ?> need attention</div>
    <?php endif; ?>
    <div class="sys-date"><?= date('D, d M Y') ?></div>
  </div>
</nav>

<main class="main-content">
  <div class="topbar">
    <button class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
    <div class="topbar-right">
      <span class="topbar-time" id="liveClock"></span>
    </div>
  </div>
  <div class="page-body">
