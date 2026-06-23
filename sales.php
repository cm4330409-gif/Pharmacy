<?php

require_once 'includes/header.php';
$db = getDB();

$date  = $_GET['date'] ?? date('Y-m-d');
$from  = $_GET['from'] ?? '';
$to    = $_GET['to'] ?? '';
$page  = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1";
if ($from && $to) {
    $where .= " AND DATE(s.sale_date) BETWEEN '$from' AND '$to'";
} elseif ($date) {
    $where .= " AND DATE(s.sale_date) = '$date'";
}

$totals = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as rev, COALESCE(SUM(discount),0) as disc FROM sales s $where")->fetch_assoc();
$total  = (int)$totals['cnt'];
$pages  = ceil($total / $perPage);

$sales = $db->query("
  SELECT s.*, COUNT(si.id) as item_count
  FROM sales s
  LEFT JOIN sale_items si ON si.sale_id = s.id
  $where GROUP BY s.id ORDER BY s.sale_date DESC LIMIT $perPage OFFSET $offset
");
?>

<div class="page-header">
  <div>
    <div class="page-title">Sales History</div>
    <div class="page-subtitle"><?= $total ?> transactions · KSh <?= number_format($totals['rev'],2) ?> total</div>
  </div>
  <a href="new_sale.php" class="btn btn-primary">⊕ New Sale</a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <div class="form-group" style="margin:0">
        <label style="font-size:11px">Single Date</label>
        <input type="date" name="date" value="<?= $date ?>" style="width:160px">
      </div>
      <div style="color:var(--text-mute);font-size:13px;padding-top:20px">or range:</div>
      <div class="form-group" style="margin:0">
        <label style="font-size:11px">From</label>
        <input type="date" name="from" value="<?= $from ?>" style="width:150px">
      </div>
      <div class="form-group" style="margin:0">
        <label style="font-size:11px">To</label>
        <input type="date" name="to" value="<?= $to ?>" style="width:150px">
      </div>
      <div style="padding-top:20px">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="sales.php" class="btn btn-outline btn-sm" style="margin-left:6px">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Summary Bar -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
  <div class="stat-card emerald"><div class="stat-icon">💰</div>
    <div><div class="stat-value">KSh <?= number_format($totals['rev'],0) ?></div><div class="stat-label">Gross Revenue</div></div></div>
  <div class="stat-card info"><div class="stat-icon">🧾</div>
    <div><div class="stat-value"><?= $total ?></div><div class="stat-label">Transactions</div></div></div>
  <div class="stat-card warn"><div class="stat-icon">🔖</div>
    <div><div class="stat-value">KSh <?= number_format($totals['disc'],0) ?></div><div class="stat-label">Total Discounts</div></div></div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">Transactions</div>
    <a href="reports.php?date=<?= $date ?>" class="btn btn-outline btn-sm">📊 Daily Report</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Invoice</th><th>Date &amp; Time</th><th>Customer</th><th>Items</th>
        <th>Discount</th><th>Total</th><th>Payment</th><th>Action</th>
      </tr></thead>
      <tbody>
      <?php while ($r = $sales->fetch_assoc()): ?>
      <tr>
        <td><a href="invoice.php?id=<?= $r['id'] ?>" style="color:var(--emerald);font-family:var(--mono);font-size:12px;font-weight:600"><?= htmlspecialchars($r['invoice_number']) ?></a></td>
        <td style="font-size:12px;color:var(--text-mute)"><?= date('d M Y H:i', strtotime($r['sale_date'])) ?></td>
        <td>
          <div><?= htmlspecialchars($r['customer_name']) ?></div>
          <?php if($r['customer_phone']): ?><small style="color:var(--text-mute)"><?= htmlspecialchars($r['customer_phone']) ?></small><?php endif; ?>
        </td>
        <td style="text-align:center;font-family:var(--mono)"><?= $r['item_count'] ?></td>
        <td style="font-family:var(--mono);color:var(--warn)"><?= $r['discount'] > 0 ? '-KSh '.number_format($r['discount'],2) : '—' ?></td>
        <td style="font-family:var(--mono);font-weight:700;color:var(--emerald)">KSh <?= number_format($r['total_amount'],2) ?></td>
        <td>
          <?php $pIcons = ['cash'=>'💵','card'=>'💳','online'=>'📱']; ?>
          <span class="tag tag-info"><?= $pIcons[$r['payment_method']]??'' ?> <?= $r['payment_method'] ?></span>
        </td>
        <td><a href="invoice.php?id=<?= $r['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
      </tr>
      <?php endwhile; ?>
      <?php if ($total === 0): ?>
      <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-mute)">No sales found for selected period</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <div style="padding:16px 20px">
    <div class="pagination">
      <?php for($i=1; $i<=$pages; $i++): ?>
      <a href="?p=<?= $i ?>&date=<?= $date ?>&from=<?= $from ?>&to=<?= $to ?>"
         class="<?= $i===$page?'active-page':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
