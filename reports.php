<?php
require_once 'includes/header.php';
$db = getDB();

$date = $_GET['date'] ?? date('Y-m-d');
$reportType = $_GET['type'] ?? 'daily';

// ── Daily Stats
$daySales = $db->query("
  SELECT s.*
  FROM sales s WHERE DATE(s.sale_date)='$date'
  ORDER BY s.sale_date DESC
");
$daySummary = $db->query("
  SELECT COUNT(*) as cnt,
    COALESCE(SUM(total_amount),0) as revenue,
    COALESCE(SUM(discount),0) as discounts,
    COALESCE(AVG(total_amount),0) as avg_sale,
    SUM(payment_method='cash') as cash_cnt,
    SUM(payment_method='card') as card_cnt,
    SUM(payment_method='online') as online_cnt,
    COALESCE(SUM(CASE WHEN payment_method='cash' THEN total_amount END),0) as cash_rev,
    COALESCE(SUM(CASE WHEN payment_method='card' THEN total_amount END),0) as card_rev,
    COALESCE(SUM(CASE WHEN payment_method='online' THEN total_amount END),0) as online_rev
  FROM sales WHERE DATE(sale_date)='$date'
")->fetch_assoc();

// ── Top selling medicines today
$topMeds = $db->query("
  SELECT m.name, SUM(si.quantity) as total_qty, SUM(si.subtotal) as total_rev
  FROM sale_items si
  JOIN sales s ON s.id = si.sale_id
  JOIN medicines m ON m.id = si.medicine_id
  WHERE DATE(s.sale_date)='$date'
  GROUP BY m.id ORDER BY total_rev DESC LIMIT 10
");

// ── 7-day trend
$trend = $db->query("
  SELECT DATE(sale_date) as d,
    COUNT(*) as cnt,
    COALESCE(SUM(total_amount),0) as rev
  FROM sales
  WHERE sale_date >= DATE_SUB('$date', INTERVAL 6 DAY)
    AND sale_date <= DATE_ADD('$date', INTERVAL 1 DAY)
  GROUP BY DATE(sale_date)
  ORDER BY d
");
$trendData = []; while ($r = $trend->fetch_assoc()) $trendData[$r['d']] = $r;
$tMax = max(array_column($trendData, 'rev') ?: [1]);

// ── Monthly summary
$monthSummary = $db->query("
  SELECT
    DAY(sale_date) as day_num,
    COUNT(*) as cnt,
    SUM(total_amount) as rev
  FROM sales
  WHERE MONTH(sale_date)=MONTH('$date') AND YEAR(sale_date)=YEAR('$date')
  GROUP BY DAY(sale_date)
  ORDER BY day_num
");
$monthTotal = $db->query("SELECT COALESCE(SUM(total_amount),0) as t, COUNT(*) as c FROM sales WHERE MONTH(sale_date)=MONTH('$date') AND YEAR(sale_date)=YEAR('$date')")->fetch_assoc();
?>

<div class="page-header">
  <div>
    <div class="page-title">Sales Reports</div>
    <div class="page-subtitle">Daily performance and trends analysis</div>
  </div>
  <div style="display:flex;gap:8px">
    <button class="btn btn-outline no-print" onclick="window.print()">🖨 Print Report</button>
    <a href="sales.php" class="btn btn-outline no-print">Sales History</a>
  </div>
</div>

<!-- Date Picker -->
<div class="card no-print" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px">
    <form method="GET" style="display:flex;gap:12px;align-items:center">
      <label style="font-weight:600;color:var(--text-mute)">Report Date:</label>
      <input type="date" name="date" value="<?= $date ?>" style="width:180px">
      <button type="submit" class="btn btn-primary btn-sm">Generate</button>
      <a href="reports.php?date=<?= date('Y-m-d') ?>" class="btn btn-outline btn-sm">Today</a>
      <a href="reports.php?date=<?= date('Y-m-d', strtotime('-1 day')) ?>" class="btn btn-outline btn-sm">Yesterday</a>
    </form>
  </div>
</div>

<!-- Daily Report Title (print) -->
<div style="text-align:center;margin-bottom:24px">
  <div style="font-size:20px;font-weight:700;color:var(--navy)">
    Daily Sales Report — <?= date('l, d F Y', strtotime($date)) ?>
  </div>
  <div style="color:var(--text-mute);font-size:13px">Generated: <?= date('d M Y H:i') ?></div>
</div>

<!-- KPI Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="stat-card emerald"><div class="stat-icon">💰</div>
    <div><div class="stat-value">KSh <?= number_format($daySummary['revenue'],0) ?></div><div class="stat-label">Day Revenue</div></div></div>
  <div class="stat-card info"><div class="stat-icon">🧾</div>
    <div><div class="stat-value"><?= (int)$daySummary['cnt'] ?></div><div class="stat-label">Transactions</div></div></div>
  <div class="stat-card warn"><div class="stat-icon">📊</div>
    <div><div class="stat-value">KSh <?= number_format($daySummary['avg_sale'],0) ?></div><div class="stat-label">Average Sale</div></div></div>
  <div class="stat-card info"><div class="stat-icon">🔖</div>
    <div><div class="stat-value">KSh <?= number_format($daySummary['discounts'],0) ?></div><div class="stat-label">Discounts Given</div></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

  <!-- Payment Breakdown -->
  <div class="card">
    <div class="card-header"><div class="card-title">Payment Breakdown</div></div>
    <div class="card-body">
      <?php
      $methods = [
        'cash'   => ['icon'=>'💵','label'=>'Cash',  'cnt'=>$daySummary['cash_cnt'],  'rev'=>$daySummary['cash_rev']],
        'card'   => ['icon'=>'💳','label'=>'Card',  'cnt'=>$daySummary['card_cnt'],  'rev'=>$daySummary['card_rev']],
        'online' => ['icon'=>'📱','label'=>'Online','cnt'=>$daySummary['online_cnt'],'rev'=>$daySummary['online_rev']],
      ];
      foreach ($methods as $m):
        $pct = $daySummary['revenue'] > 0 ? round(($m['rev']/$daySummary['revenue'])*100) : 0;
      ?>
      <div style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
          <span style="font-weight:600"><?= $m['icon'] ?> <?= $m['label'] ?> <small style="color:var(--text-mute)">(<?= $m['cnt'] ?> sales)</small></span>
          <span style="font-family:var(--mono);font-weight:600">KSh <?= number_format($m['rev'],2) ?></span>
        </div>
        <div class="stock-bar" style="width:100%;height:8px">
          <div class="stock-bar-fill bar-ok" style="width:<?= $pct ?>%"></div>
        </div>
        <div style="font-size:11px;color:var(--text-mute);margin-top:2px"><?= $pct ?>% of daily revenue</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- 7-day trend chart -->
  <div class="card">
    <div class="card-header"><div class="card-title">7-Day Revenue Trend</div></div>
    <div class="card-body">
      <div class="mini-chart" style="height:80px">
        <?php for ($i = 6; $i >= 0; $i--):
          $d = date('Y-m-d', strtotime("$date -$i days"));
          $rev = $trendData[$d]['rev'] ?? 0;
          $cnt = $trendData[$d]['cnt'] ?? 0;
          $pct = $tMax > 0 ? max(3, round(($rev/$tMax)*70)) : 3;
          $isToday = $d === $date;
        ?>
        <div class="mini-bar-wrap">
          <div class="mini-bar" style="height:<?= $pct ?>px;opacity:<?= $isToday?1:.6 ?>;background:<?= $isToday?'var(--emerald)':'var(--navy-soft)' ?>" title="KSh <?= number_format($rev,0) ?> · <?= $cnt ?> sales"></div>
          <div class="mini-bar-label"><?= date('D', strtotime($d)) ?></div>
        </div>
        <?php endfor; ?>
      </div>
      <!-- Trend table -->
      <table style="font-size:12px;margin-top:8px">
        <thead><tr><th>Date</th><th>Sales</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php for ($i=6; $i>=0; $i--):
          $d = date('Y-m-d', strtotime("$date -$i days"));
          $entry = $trendData[$d] ?? ['cnt'=>0,'rev'=>0];
        ?>
        <tr <?= $d===$date?'style="background:rgba(0,184,148,.07);font-weight:600"':'' ?>>
          <td><?= date('D d/m', strtotime($d)) ?></td>
          <td style="text-align:center"><?= $entry['cnt'] ?></td>
          <td style="font-family:var(--mono)">KSh <?= number_format($entry['rev'],2) ?></td>
        </tr>
        <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Top Medicines -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title">Top Selling Medicines — <?= date('d M Y', strtotime($date)) ?></div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Rank</th><th>Medicine</th><th>Units Sold</th><th>Revenue</th><th>Revenue Share</th></tr></thead>
      <tbody>
      <?php $rank=0; while ($r = $topMeds->fetch_assoc()):
        $rank++;
        $share = $daySummary['revenue'] > 0 ? round(($r['total_rev']/$daySummary['revenue'])*100) : 0;
      ?>
      <tr>
        <td style="font-family:var(--mono);font-weight:700;color:<?= $rank<=3?'var(--emerald)':'var(--text-mute)' ?>">#<?= $rank ?></td>
        <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
        <td style="font-family:var(--mono)"><?= $r['total_qty'] ?> units</td>
        <td style="font-family:var(--mono);font-weight:600">KSh <?= number_format($r['total_rev'],2) ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div class="stock-bar" style="width:80px"><div class="stock-bar-fill bar-ok" style="width:<?= $share ?>%"></div></div>
            <span style="font-size:12px;color:var(--text-mute)"><?= $share ?>%</span>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
      <?php if ($rank === 0): ?><tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-mute)">No sales recorded for this date</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- All Transactions Today -->
<div class="card">
  <div class="card-header">
    <div class="card-title">All Transactions — <?= date('d M Y', strtotime($date)) ?></div>
    <div style="font-family:var(--mono);font-weight:700;color:var(--emerald)">Total: KSh <?= number_format($daySummary['revenue'],2) ?></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Time</th><th>Invoice</th><th>Customer</th><th>Payment</th><th>Discount</th><th>Total</th></tr></thead>
      <tbody>
      <?php
      $daySales->data_seek(0);
      $found = false;
      while ($r = $daySales->fetch_assoc()):
        $found = true;
      ?>
      <tr>
        <td style="font-family:var(--mono);font-size:12px"><?= date('H:i', strtotime($r['sale_date'])) ?></td>
        <td><a href="invoice.php?id=<?= $r['id'] ?>" style="color:var(--emerald);font-family:var(--mono);font-size:12px"><?= htmlspecialchars($r['invoice_number']) ?></a></td>
        <td><?= htmlspecialchars($r['customer_name']) ?></td>
        <td><span class="tag tag-info"><?= $r['payment_method'] ?></span></td>
        <td style="font-family:var(--mono);color:var(--warn)"><?= $r['discount']>0 ? 'KSh '.number_format($r['discount'],2) : '—' ?></td>
        <td style="font-family:var(--mono);font-weight:700">KSh <?= number_format($r['total_amount'],2) ?></td>
      </tr>
      <?php endwhile; ?>
      <?php if (!$found): ?><tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-mute)">No transactions for <?= date('d M Y', strtotime($date)) ?></td></tr><?php endif; ?>
      </tbody>
      <?php if ($found): ?>
      <tfoot>
        <tr style="background:var(--navy);color:white">
          <td colspan="4" style="padding:12px 14px;font-weight:600">DAILY TOTAL — <?= $daySummary['cnt'] ?> transactions</td>
          <td style="padding:12px 14px;font-family:var(--mono)">-KSh <?= number_format($daySummary['discounts'],2) ?></td>
          <td style="padding:12px 14px;font-family:var(--mono);font-size:16px;font-weight:700;color:var(--emerald)">KSh <?= number_format($daySummary['revenue'],2) ?></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<!-- Monthly Summary -->
<div class="card">
  <div class="card-header">
    <div class="card-title">📅 Month Summary — <?= date('F Y', strtotime($date)) ?></div>
    <div style="font-family:var(--mono);font-weight:700;color:var(--emerald)">Month Total: KSh <?= number_format($monthTotal['t'],2) ?> · <?= $monthTotal['c'] ?> sales</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Day</th><th>Sales Count</th><th>Revenue</th></tr></thead>
      <tbody>
      <?php while ($r = $monthSummary->fetch_assoc()):
        $dayDate = date('Y-m', strtotime($date)) . '-' . str_pad($r['day_num'],2,'0',STR_PAD_LEFT);
      ?>
      <tr <?= $dayDate===$date?'style="background:rgba(0,184,148,.07);font-weight:600"':'' ?>>
        <td><?= date('D d', strtotime($dayDate)) ?></td>
        <td style="font-family:var(--mono)"><?= $r['cnt'] ?></td>
        <td style="font-family:var(--mono)">KSh <?= number_format($r['rev'],2) ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
