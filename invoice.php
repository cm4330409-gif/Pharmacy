<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$sale = $db->query("SELECT * FROM sales WHERE id=$id")->fetch_assoc();
if (!$sale) { echo '<div class="alert alert-danger">Invoice not found.</div>'; require_once 'includes/footer.php'; exit; }

$items = $db->query("
  SELECT si.*, m.name as med_name, m.unit, m.generic_name
  FROM sale_items si
  JOIN medicines m ON m.id = si.medicine_id
  WHERE si.sale_id = $id
");
?>

<div class="page-header no-print">
  <div class="page-title">Invoice — <?= htmlspecialchars($sale['invoice_number']) ?></div>
  <div style="display:flex;gap:10px">
    <button class="btn btn-outline" onclick="window.print()">🖨 Print</button>
    <a href="sales.php" class="btn btn-outline">← Back</a>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <!-- Invoice Header -->
    <div class="invoice-header">
      <div>
        <div class="invoice-title">⚕ PharmaCare</div>
        <div style="color:var(--text-mute);font-size:13px;margin-top:4px">Online Pharmacy Management System</div>
        <div style="color:var(--text-mute);font-size:12px">Tel: +254 800 000 000 | pharmacy@pharmacare.co.ke</div>
      </div>
      <div class="invoice-meta">
        <div style="font-size:22px;font-weight:700;color:var(--emerald)">TAX INVOICE</div>
        <div><strong><?= htmlspecialchars($sale['invoice_number']) ?></strong></div>
        <div>Date: <?= date('d M Y', strtotime($sale['sale_date'])) ?></div>
        <div>Time: <?= date('H:i', strtotime($sale['sale_date'])) ?></div>
      </div>
    </div>

    <!-- Customer -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
      <div style="background:var(--grey-100);padding:16px;border-radius:var(--radius)">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mute);margin-bottom:8px">Bill To</div>
        <div style="font-weight:600;font-size:15px"><?= htmlspecialchars($sale['customer_name']) ?></div>
        <?php if($sale['customer_phone']): ?>
        <div style="color:var(--text-mute);font-size:13px"><?= htmlspecialchars($sale['customer_phone']) ?></div>
        <?php endif; ?>
      </div>
      <div style="background:var(--grey-100);padding:16px;border-radius:var(--radius)">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-mute);margin-bottom:8px">Payment Info</div>
        <div style="font-weight:600">Method: <?= strtoupper($sale['payment_method']) ?></div>
        <div style="color:var(--text-mute);font-size:13px">Status: <span style="color:var(--success);font-weight:600">PAID</span></div>
      </div>
    </div>

    <!-- Items Table -->
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>#</th><th>Medicine</th><th>Generic Name</th><th>Unit</th>
          <th style="text-align:right">Unit Price</th><th style="text-align:center">Qty</th>
          <th style="text-align:right">Subtotal</th>
        </tr></thead>
        <tbody>
        <?php $n = 0; while ($r = $items->fetch_assoc()): $n++; ?>
        <tr>
          <td style="color:var(--text-mute)"><?= $n ?></td>
          <td><strong><?= htmlspecialchars($r['med_name']) ?></strong></td>
          <td style="color:var(--text-mute);font-size:12px"><?= htmlspecialchars($r['generic_name']??'—') ?></td>
          <td><?= $r['unit'] ?></td>
          <td style="text-align:right;font-family:var(--mono)">KSh <?= number_format($r['unit_price'],2) ?></td>
          <td style="text-align:center;font-family:var(--mono)"><?= $r['quantity'] ?></td>
          <td style="text-align:right;font-family:var(--mono);font-weight:600">KSh <?= number_format($r['subtotal'],2) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
        <tfoot>
          <?php if ($sale['discount'] > 0): ?>
          <tr>
            <td colspan="6" style="text-align:right;padding:10px 14px;font-weight:500">Subtotal:</td>
            <td style="text-align:right;font-family:var(--mono);padding:10px 14px">KSh <?= number_format($sale['total_amount'] + $sale['discount'], 2) ?></td>
          </tr>
          <tr>
            <td colspan="6" style="text-align:right;padding:6px 14px;color:var(--warn)">Discount:</td>
            <td style="text-align:right;font-family:var(--mono);padding:6px 14px;color:var(--warn)">-KSh <?= number_format($sale['discount'],2) ?></td>
          </tr>
          <?php endif; ?>
          <tr class="invoice-total-row">
            <td colspan="6" style="text-align:right;padding:14px">TOTAL:</td>
            <td style="text-align:right;font-family:var(--mono);padding:14px;font-size:18px;color:var(--emerald)">KSh <?= number_format($sale['total_amount'],2) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Footer -->
    <div style="margin-top:32px;padding-top:16px;border-top:1px solid var(--grey-200);display:flex;justify-content:space-between;align-items:center">
      <div style="font-size:12px;color:var(--text-mute)">
        <?php if($sale['notes']): ?>
        <div><strong>Notes:</strong> <?= htmlspecialchars($sale['notes']) ?></div>
        <?php endif; ?>
        <div style="margin-top:4px">Thank you for choosing PharmaCare!</div>
      </div>
      <div style="text-align:center">
        <div style="font-size:10px;color:var(--text-mute)">Authorised Signature</div>
        <div style="border-top:1px solid var(--grey-400);margin-top:28px;padding-top:4px;width:150px;font-size:11px;color:var(--text-mute)">Pharmacist</div>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
