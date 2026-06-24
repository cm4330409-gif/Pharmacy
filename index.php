<?php
require_once 'includes/header.php';
$db = getDB();

// ── KPI stats
$todaySales   = $db->query("SELECT COALESCE(SUM(total_amount),0) as t, COUNT(*) as c FROM sales WHERE DATE(sale_date)=CURDATE()")->fetch_assoc();
$totalMeds    = $db->query("SELECT COUNT(*) as c FROM medicines WHERE status='active'")->fetch_row()[0];
$lowStock     = $db->query("SELECT COUNT(*) as c FROM medicines WHERE stock_quantity <= min_stock_level AND status='active'")->fetch_row()[0];
$expiringSoon = $db->query("SELECT COUNT(*) as c FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status='active'")->fetch_row()[0];
$expired      = $db->query("SELECT COUNT(*) as c FROM medicines WHERE expiry_date < CURDATE() AND status='active'")->fetch_row()[0];
$monthRevenue = $db->query("SELECT COALESCE(SUM(total_amount),0) as t FROM sales WHERE MONTH(sale_date)=MONTH(CURDATE()) AND YEAR(sale_date)=YEAR(CURDATE())")->fetch_row()[0];

// ── 7-day sales chart data
$chart = $db->query("
  SELECT DATE(sale_date) as d, COALESCE(SUM(total_amount),0) as rev
  FROM sales
  WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
  GROUP BY DATE(sale_date)
  ORDER BY d
");
$chartData = []; while ($r = $chart->fetch_assoc()) $chartData[$r['d']] = $r['rev'];
$chartMax = max(array_values($chartData) ?: [1]);

// ── Recent sales
$recentSales = $db->query("SELECT * FROM sales ORDER BY sale_date DESC LIMIT 8");

// ── Critical stock
$critStock = $db->query("SELECT name, stock_quantity, min_stock_level, expiry_date FROM medicines WHERE stock_quantity <= min_stock_level AND status='active' ORDER BY stock_quantity ASC LIMIT 6");
?>

<div class="page-header">
  <div>
    <div class="page-title">Dashboard</div>
    <div class="page-subtitle">Welcome back — here's your pharmacy overview for today</div>
  </div>
  <a href="new_sale.php" class="btn btn-primary">⊕ New Sale</a>
</div>

<!-- KPI Cards -->
<div class="stats-grid">
  <div class="stat-card emerald">
    <div class="stat-icon">💰</div>
    <div>
      <div class="stat-value">KSh <?= number_format($todaySales['t'], 0) ?></div>
      <div class="stat-label">Today's Revenue (<?= $todaySales['c'] ?> sales)</div>
    </div>
  </div>
  <div class="stat-card info">
    <div class="stat-icon">💊</div>
    <div>
      <div class="stat-value"><?= $totalMeds ?></div>
      <div class="stat-label">Active Medicines</div>
    </div>
  </div>
  <div class="stat-card warn">
    <div class="stat-icon">⚠</div>
    <div>
      <div class="stat-value"><?= $lowStock ?></div>
      <div class="stat-label">Low Stock Items</div>
    </div>
  </div>
  <div class="stat-card danger">
    <div class="stat-icon">⏱</div>
    <div>
      <div class="stat-value"><?= $expired + $expiringSoon ?></div>
      <div class="stat-label"><?= $expired ?> Expired · <?= $expiringSoon ?> Expiring</div>
    </div>
  </div>
  <div class="stat-card emerald">
    <div class="stat-icon">📈</div>
    <div>
      <div class="stat-value">KSh <?= number_format($monthRevenue, 0) ?></div>
      <div class="stat-label">Month Revenue</div>
    </div>
  </div>
</div>

<!-- Charts + Alerts row -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">

  <!-- 7-day Chart -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">📊 Last 7 Days — Revenue</div>
    </div>
    <div class="card-body">
      <div class="mini-chart">
        <?php
        for ($i = 6; $i >= 0; $i--) {
          $day = date('Y-m-d', strtotime("-{$i} days"));
          $rev = $chartData[$day] ?? 0;
          $pct = $chartMax > 0 ? round(($rev / $chartMax) * 56) : 3;
          $label = date('D', strtotime($day));
          echo "<div class='mini-bar-wrap'>
            <div class='mini-bar' style='height:{$pct}px' title='KSh ".number_format($rev,0)."'></div>
            <div class='mini-bar-label'>$label</div>
          </div>";
        }
        ?>
      </div>
    </div>
  </div>

  <!-- Quick alerts -->
  <div class="card">
    <div class="card-header"><div class="card-title">🔔 Alerts</div></div>
    <div class="card-body" style="padding:12px">
      <?php if ($expired): ?>
      <a href="expiry.php?filter=expired" style="display:block;text-decoration:none">
        <div class="alert alert-danger" style="margin-bottom:8px">
          ❌ <?= $expired ?> medicine<?= $expired>1?'s':'' ?> EXPIRED
        </div>
      </a>
      <?php endif; ?>
      <?php if ($expiringSoon): ?>
      <a href="expiry.php?filter=soon" style="display:block;text-decoration:none">
        <div class="alert alert-warn" style="margin-bottom:8px">
          ⚠ <?= $expiringSoon ?> expiring within 30 days
        </div>
      </a>
      <?php endif; ?>
      <?php if ($lowStock): ?>
      <a href="stock.php?filter=low" style="display:block;text-decoration:none">
        <div class="alert alert-warn" style="margin-bottom:8px">
          📦 <?= $lowStock ?> items below minimum stock
        </div>
      </a>
      <?php endif; ?>
      <?php if (!$expired && !$expiringSoon && !$lowStock): ?>
      <div class="alert alert-success">✅ All systems normal</div>
      <?php endif; ?>
      <div style="margin-top:8px">
        <a href="reports.php" class="btn btn-outline btn-sm" style="width:100%;justify-content:center">View Daily Report →</a>
      </div>
    </div>
  </div>
</div>

<!-- Bottom row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

  <!-- Recent Sales -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Recent Sales</div>
      <a href="sales.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Invoice</th><th>Customer</th><th>Amount</th><th>Method</th></tr></thead>
        <tbody>
        <?php while($r = $recentSales->fetch_assoc()): ?>
        <tr>
          <td><a href="invoice.php?id=<?= $r['id'] ?>" style="color:var(--emerald);font-family:var(--mono);font-size:12px"><?= htmlspecialchars($r['invoice_number']) ?></a></td>
          <td><?= htmlspecialchars($r['customer_name']) ?></td>
          <td style="font-family:var(--mono);font-weight:600">KSh <?= number_format($r['total_amount'],2) ?></td>
          <td><span class="tag tag-info"><?= $r['payment_method'] ?></span></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
        
      </table>
    </div>
  </div>

  <!-- Critical Stock -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">⚠ Critical Stock</div>
      <a href="stock.php" class="btn btn-outline btn-sm">Manage Stock</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Medicine</th><th>Stock</th><th>Status</th></tr></thead>
        <tbody>
        <?php while($r = $critStock->fetch_assoc()):
          $pct = $r['min_stock_level'] > 0 ? min(100, round(($r['stock_quantity']/$r['min_stock_level'])*100)) : 100;
          $cls = $r['stock_quantity'] == 0 ? 'bar-danger' : ($pct < 50 ? 'bar-warn' : 'bar-ok');
        ?>
        <tr>
          <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></td>
          <td>
            <div class="stock-bar-wrap">
              <div class="stock-bar"><div class="stock-bar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
              <span style="font-family:var(--mono);font-size:12px"><?= $r['stock_quantity'] ?></span>
            </div>
          </td>
          <td><?php if($r['stock_quantity']==0): ?><span class="tag tag-danger">Out of Stock</span><?php else: ?><span class="tag tag-warn">Low</span><?php endif; ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if ($lowStock == 0): ?><tr><td colspan="3" style="text-align:center;color:var(--text-mute);padding:20px">✅ All stock levels healthy</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
