<?php

require_once 'includes/header.php';
$db = getDB();

$msg = ''; $msgType = 'success';

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medId = (int)$_POST['medicine_id'];
    $qty   = (int)$_POST['quantity'];
    $type  = $_POST['type'] ?? 'restock';
    $reason = trim($_POST['reason'] ?? '');

    $sign = in_array($type, ['damage','return']) ? -1 : 1;
    $change = $sign * abs($qty);

    $db->query("UPDATE medicines SET stock_quantity = GREATEST(0, stock_quantity + ($change)) WHERE id=$medId");
    $stmt = $db->prepare("INSERT INTO stock_adjustments (medicine_id, adjustment_type, quantity_change, reason) VALUES (?,?,?,?)");
    $stmt->bind_param('isis', $medId, $type, $change, $reason);
    $stmt->execute();
    $msg = "Stock adjusted successfully. Change: " . ($change >= 0 ? '+' : '') . "$change units.";
}

$filter = $_GET['filter'] ?? 'all';
$where = "WHERE m.status = 'active'";
if ($filter === 'low')  $where .= " AND m.stock_quantity <= m.min_stock_level";
if ($filter === 'out')  $where .= " AND m.stock_quantity = 0";
if ($filter === 'ok')   $where .= " AND m.stock_quantity > m.min_stock_level";

$search = trim($_GET['q'] ?? '');
if ($search) $where .= " AND m.name LIKE '%".addslashes($search)."%'";

$meds = $db->query("
  SELECT m.*, c.name as cat_name,
    ROUND((m.stock_quantity / NULLIF(m.min_stock_level,0)) * 100) as stock_pct
  FROM medicines m
  LEFT JOIN categories c ON c.id = m.category_id
  $where ORDER BY m.stock_quantity ASC, m.name ASC
");

$allMeds = $db->query("SELECT id, name, unit FROM medicines WHERE status='active' ORDER BY name");

// Count stats
$stats = $db->query("SELECT
  COUNT(*) as total,
  SUM(stock_quantity = 0) as out_of_stock,
  SUM(stock_quantity > 0 AND stock_quantity <= min_stock_level) as low,
  SUM(stock_quantity > min_stock_level) as adequate
  FROM medicines WHERE status='active'")->fetch_assoc();

// Recent adjustments
$adjustments = $db->query("
  SELECT a.*, m.name as med_name, m.unit
  FROM stock_adjustments a
  JOIN medicines m ON m.id = a.medicine_id
  ORDER BY a.adjusted_at DESC LIMIT 10
");
?>

<div class="page-header">
  <div>
    <div class="page-title">Stock Management</div>
    <div class="page-subtitle">Track inventory levels and make adjustments</div>
  </div>
  <button class="btn btn-primary" onclick="toggleForm('adjust-form')">⊕ Adjust Stock</button>
</div>

<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="stat-card info"><div class="stat-icon">📦</div>
    <div><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">Total Products</div></div></div>
  <div class="stat-card danger"><div class="stat-icon">❌</div>
    <div><div class="stat-value"><?= (int)$stats['out_of_stock'] ?></div><div class="stat-label">Out of Stock</div></div></div>
  <div class="stat-card warn"><div class="stat-icon">⚠</div>
    <div><div class="stat-value"><?= (int)$stats['low'] ?></div><div class="stat-label">Low Stock</div></div></div>
  <div class="stat-card emerald"><div class="stat-icon">✅</div>
    <div><div class="stat-value"><?= (int)$stats['adequate'] ?></div><div class="stat-label">Adequate</div></div></div>
</div>

<!-- Adjust Form -->
<div id="adjust-form" class="card" style="display:none;margin-bottom:20px">
  <div class="card-header">
    <div class="card-title">Stock Adjustment</div>
    <button class="btn btn-outline btn-sm" onclick="toggleForm('adjust-form')">✕</button>
  </div>
  <div class="card-body">
    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label>Medicine *</label>
          <select name="medicine_id" required>
            <option value="">-- Select Medicine --</option>
            <?php while($r = $allMeds->fetch_assoc()): ?>
            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?> (<?= $r['unit'] ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Adjustment Type *</label>
          <select name="type">
            <option value="restock">🔄 Restock (Add)</option>
            <option value="damage">⚠ Damage / Expired (Remove)</option>
            <option value="return">↩ Customer Return (Remove)</option>
            <option value="correction">✏ Stock Correction (Add)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Quantity *</label>
          <input type="number" name="quantity" min="1" required placeholder="Enter units">
        </div>
        <div class="form-group full">
          <label>Reason / Notes</label>
          <input name="reason" placeholder="Optional: reason for adjustment">
        </div>
      </div>
      <div style="margin-top:16px"><button type="submit" class="btn btn-primary">💾 Save Adjustment</button></div>
    </form>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">

<!-- Stock Table -->
<div class="card">
  <div class="card-header">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input name="q" placeholder="Search…" value="<?= htmlspecialchars($search) ?>" style="width:200px">
      <?php foreach(['all'=>'All','out'=>'Out of Stock','low'=>'Low','ok'=>'Adequate'] as $k=>$l): ?>
      <a href="?filter=<?= $k ?>" class="btn btn-sm <?= $filter===$k?'btn-primary':'btn-outline' ?>"><?= $l ?></a>
      <?php endforeach; ?>
      <?php if ($search): ?><a href="stock.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Medicine</th><th>Category</th><th>Current Stock</th><th>Min Level</th><th>Level</th><th>Status</th></tr></thead>
      <tbody>
      <?php $found = false; while ($r = $meds->fetch_assoc()):
        $found = true;
        $pct = min(100, (int)$r['stock_pct']);
        if ($r['stock_quantity'] === 0) { $cls = 'bar-danger'; $stag = 'tag-danger'; $slabel = 'Out of Stock'; }
        elseif ($r['stock_quantity'] <= $r['min_stock_level']) { $cls = 'bar-warn'; $stag = 'tag-warn'; $slabel = 'Low'; }
        else { $cls = 'bar-ok'; $stag = 'tag-ok'; $slabel = 'Adequate'; }
      ?>
      <tr>
        <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
        <td style="color:var(--text-mute);font-size:12px"><?= htmlspecialchars($r['cat_name']??'—') ?></td>
        <td style="font-family:var(--mono);font-weight:700"><?= $r['stock_quantity'] ?> <small style="color:var(--text-mute)"><?= $r['unit'] ?>s</small></td>
        <td style="font-family:var(--mono);color:var(--text-mute)"><?= $r['min_stock_level'] ?></td>
        <td>
          <div class="stock-bar-wrap">
            <div class="stock-bar" style="width:100px"><div class="stock-bar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
            <small style="font-family:var(--mono);color:var(--text-mute)"><?= $pct ?>%</small>
          </div>
        </td>
        <td><span class="tag <?= $stag ?>"><?= $slabel ?></span></td>
      </tr>
      <?php endwhile; ?>
      <?php if(!$found): ?><tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-mute)">No records found</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Recent Adjustments -->
<div class="card">
  <div class="card-header"><div class="card-title">Recent Adjustments</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Medicine</th><th>Change</th><th>Type</th><th>Date</th></tr></thead>
      <tbody>
      <?php while ($r = $adjustments->fetch_assoc()):
        $pos = $r['quantity_change'] >= 0;
      ?>
      <tr>
        <td style="font-size:12px"><?= htmlspecialchars($r['med_name']) ?></td>
        <td style="font-family:var(--mono);font-weight:700;color:<?= $pos?'var(--success)':'var(--danger)' ?>">
          <?= $pos ? '+' : '' ?><?= $r['quantity_change'] ?>
        </td>
        <td><span class="tag <?= $pos ? 'tag-ok' : 'tag-warn' ?>" style="font-size:10px"><?= $r['adjustment_type'] ?></span></td>
        <td style="font-size:11px;color:var(--text-mute)"><?= date('d/m H:i', strtotime($r['adjusted_at'])) ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<script>
function toggleForm(id) {
  const f = document.getElementById(id);
  f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>
