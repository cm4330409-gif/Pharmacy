<?php

require_once 'includes/header.php';
$db = getDB();

$filter = $_GET['filter'] ?? 'all';

$whereMap = [
  'expired' => "WHERE m.expiry_date < CURDATE() AND m.status='active'",
  'soon'    => "WHERE m.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND m.status='active'",
  '60days'  => "WHERE m.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND m.status='active'",
  'all'     => "WHERE m.status='active' ORDER BY m.expiry_date ASC",
];
$where = $whereMap[$filter] ?? $whereMap['all'];
if ($filter !== 'all') $where .= " ORDER BY m.expiry_date ASC";

$meds = $db->query("
  SELECT m.*, c.name as cat_name, s.name as sup_name,
    DATEDIFF(m.expiry_date, CURDATE()) as days_left
  FROM medicines m
  LEFT JOIN categories c ON c.id = m.category_id
  LEFT JOIN suppliers s ON s.id = m.supplier_id
  $where
");

$counts = $db->query("
  SELECT
    SUM(expiry_date < CURDATE()) as expired,
    SUM(expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)) as in30,
    SUM(expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)) as in60
  FROM medicines WHERE status='active'
")->fetch_assoc();
?>

<div class="page-header">
  <div>
    <div class="page-title">Expiry Tracker</div>
    <div class="page-subtitle">Monitor medicine shelf life and take timely action</div>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <a href="expiry.php?filter=expired" style="text-decoration:none">
    <div class="stat-card danger">
      <div class="stat-icon">💀</div>
      <div><div class="stat-value"><?= (int)$counts['expired'] ?></div><div class="stat-label">Expired</div></div>
    </div>
  </a>
  <a href="expiry.php?filter=soon" style="text-decoration:none">
    <div class="stat-card danger">
      <div class="stat-icon">🔴</div>
      <div><div class="stat-value"><?= (int)$counts['in30'] ?></div><div class="stat-label">Expiring ≤ 30 days</div></div>
    </div>
  </a>
  <a href="expiry.php?filter=60days" style="text-decoration:none">
    <div class="stat-card warn">
      <div class="stat-icon">🟡</div>
      <div><div class="stat-value"><?= (int)$counts['in60'] ?></div><div class="stat-label">Expiring ≤ 60 days</div></div>
    </div>
  </a>
  <a href="expiry.php?filter=all" style="text-decoration:none">
    <div class="stat-card info">
      <div class="stat-icon">📋</div>
      <div><div class="stat-value">All</div><div class="stat-label">View All Active</div></div>
    </div>
  </a>
</div>

<!-- Filter tabs -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
  <?php foreach(['all'=>'All Active','expired'=>'Expired','soon'=>'Expiring ≤30d','60days'=>'Expiring ≤60d'] as $k=>$label): ?>
  <a href="?filter=<?= $k ?>" class="btn <?= $filter===$k?'btn-primary':'btn-outline' ?> btn-sm"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if ($filter === 'expired' && $counts['expired'] > 0): ?>
<div class="alert alert-danger">❌ These medicines are <strong>EXPIRED</strong>. Remove from dispensing immediately and quarantine for disposal per pharmacy protocols.</div>
<?php elseif ($filter === 'soon'): ?>
<div class="alert alert-warn">⚠ These medicines expire within 30 days. Consider applying discounts or returning to supplier.</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <div class="card-title">
      <?= ['all'=>'All Active Medicines','expired'=>'Expired Medicines','soon'=>'Expiring Within 30 Days','60days'=>'Expiring Within 60 Days'][$filter] ?>
    </div>
    <button class="btn btn-outline btn-sm no-print" onclick="window.print()">🖨 Print Report</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Medicine</th><th>Batch No.</th><th>Category</th><th>Supplier</th>
        <th>Stock Qty</th><th>Expiry Date</th><th>Days Left</th><th>Urgency</th>
      </tr></thead>
      <tbody>
      <?php
      $found = false;
      while ($r = $meds->fetch_assoc()):
        $found = true;
        $d = (int)$r['days_left'];
        if ($d < 0)       { $urgency = 'tag-danger'; $label = 'EXPIRED'; $dClass = 'expired'; }
        elseif ($d <= 7)  { $urgency = 'tag-danger'; $label = 'Critical'; $dClass = 'critical'; }
        elseif ($d <= 30) { $urgency = 'tag-danger'; $label = 'Urgent';   $dClass = 'critical'; }
        elseif ($d <= 60) { $urgency = 'tag-warn';   $label = 'Warning';  $dClass = 'soon'; }
        else              { $urgency = 'tag-ok';     $label = 'OK';       $dClass = 'ok'; }
      ?>
      <tr <?= $d < 0 ? 'style="background:#fff5f5"' : ($d <= 30 ? 'style="background:#fffbf0"' : '') ?>>
        <td><strong><?= htmlspecialchars($r['name']) ?></strong>
          <?php if ($r['generic_name']): ?><small style="display:block;color:var(--text-mute)"><?= htmlspecialchars($r['generic_name']) ?></small><?php endif; ?>
        </td>
        <td><code style="font-size:12px"><?= htmlspecialchars($r['batch_number'] ?: '—') ?></code></td>
        <td><?= htmlspecialchars($r['cat_name'] ?? '—') ?></td>
        <td><?= htmlspecialchars($r['sup_name'] ?? '—') ?></td>
        <td style="font-family:var(--mono)"><?= $r['stock_quantity'] ?> <?= $r['unit'] ?>s</td>
        <td style="font-family:var(--mono)"><?= date('d M Y', strtotime($r['expiry_date'])) ?></td>
        <td>
          <span class="days-indicator <?= $dClass ?>">
            <?= $d < 0 ? abs($d).' days ago' : ($d === 0 ? 'TODAY' : $d.' days') ?>
          </span>
        </td>
        <td><span class="tag <?= $urgency ?>"><?= $label ?></span></td>
      </tr>
      <?php endwhile; ?>
      <?php if (!$found): ?>
      <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-mute)">✅ No medicines in this category</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
