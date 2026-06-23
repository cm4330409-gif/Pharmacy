<?php

require_once 'includes/header.php';
$db = getDB();

$msg = ''; $msgType = 'success'; $invoiceId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer  = trim($_POST['customer_name'] ?? 'Walk-in Customer');
    $phone     = trim($_POST['customer_phone'] ?? '');
    $discount  = (float)($_POST['discount'] ?? 0);
    $payment   = $_POST['payment_method'] ?? 'cash';
    $notes     = trim($_POST['notes'] ?? '');
    $items     = $_POST['items'] ?? [];

    $validItems = array_filter($items, fn($i) => !empty($i['medicine_id']) && (int)($i['quantity']??0) > 0);

    if (empty($validItems)) {
        $msg = 'Please add at least one item to the sale.'; $msgType = 'danger';
    } else {
        // Calculate total
        $total = 0;
        foreach ($validItems as $item) {
            $mid = (int)$item['medicine_id'];
            $qty = (int)$item['quantity'];
            $med = $db->query("SELECT selling_price, stock_quantity FROM medicines WHERE id=$mid")->fetch_assoc();
            if ($med && $med['stock_quantity'] >= $qty) {
                $total += $qty * $med['selling_price'];
            }
        }
        $net = max(0, $total - $discount);
        $invNo = 'INV-' . date('Ymd') . '-' . str_pad(rand(1,999),3,'0',STR_PAD_LEFT);

        $stmt = $db->prepare("INSERT INTO sales (invoice_number, customer_name, customer_phone, total_amount, discount, paid_amount, payment_method, notes) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssdddss', $invNo, $customer, $phone, $net, $discount, $net, $payment, $notes);
        $stmt->execute();
        $saleId = $db->insert_id;

        foreach ($validItems as $item) {
            $mid = (int)$item['medicine_id'];
            $qty = (int)$item['quantity'];
            $med = $db->query("SELECT selling_price, stock_quantity FROM medicines WHERE id=$mid")->fetch_assoc();
            if ($med) {
                $price = $med['selling_price'];
                $sub   = $qty * $price;
                $si = $db->prepare("INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, subtotal) VALUES (?,?,?,?,?)");
                $si->bind_param('iiidd', $saleId, $mid, $qty, $price, $sub);
                $si->execute();
                $db->query("UPDATE medicines SET stock_quantity = stock_quantity - $qty WHERE id=$mid");
            }
        }
        $invoiceId = $saleId;
        $msg = "Sale recorded! Invoice: $invNo"; $msgType = 'success';
    }
}

$medicines = $db->query("SELECT id, name, selling_price, stock_quantity, unit FROM medicines WHERE status='active' AND stock_quantity > 0 ORDER BY name");
$medOpts = '';
while ($r = $medicines->fetch_assoc()) {
    $medOpts .= "<option value='{$r['id']}' data-price='{$r['selling_price']}' data-stock='{$r['stock_quantity']}'>".
                htmlspecialchars($r['name'])." [{$r['unit']}] — KSh ".number_format($r['selling_price'],2)." (Stock: {$r['stock_quantity']})</option>";
}
?>

<div class="page-header">
  <div>
    <div class="page-title">New Sale</div>
    <div class="page-subtitle">Process a new customer transaction</div>
  </div>
  <?php if ($invoiceId): ?><a href="invoice.php?id=<?= $invoiceId ?>" class="btn btn-primary no-print">🖨 View Invoice</a><?php endif; ?>
</div>

<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

<form method="POST" onsubmit="return validateSale()">
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">

    <!-- Items Card -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Sale Items</div>
        <button type="button" class="btn btn-outline btn-sm" onclick="addSaleRow()">⊕ Add Item</button>
      </div>
      <div class="card-body">
        <div class="table-wrap">
          <table id="sale-items-table">
            <thead><tr><th>Medicine</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th><th></th></tr></thead>
            <tbody id="sale-rows"></tbody>
          </table>
        </div>
        <div style="margin-top:16px">
          <button type="button" class="btn btn-outline" onclick="addSaleRow()">⊕ Add Another Item</button>
        </div>
      </div>
    </div>

    <!-- Summary Card -->
    <div>
      <div class="card" style="margin-bottom:16px">
        <div class="card-header"><div class="card-title">Customer</div></div>
        <div class="card-body">
          <div class="form-group" style="margin-bottom:12px">
            <label>Customer Name</label>
            <input name="customer_name" placeholder="Walk-in Customer">
          </div>
          <div class="form-group" style="margin-bottom:12px">
            <label>Phone</label>
            <input name="customer_phone" placeholder="07xx-xxx-xxx">
          </div>
          <div class="form-group" style="margin-bottom:12px">
            <label>Payment Method</label>
            <select name="payment_method">
              <option value="cash">💵 Cash</option>
              <option value="card">💳 Card</option>
              <option value="online">📱 Online / MPESA</option>
            </select>
          </div>
          <div class="form-group">
            <label>Discount (KSh)</label>
            <input type="number" name="discount" id="discount" min="0" step="0.01" value="0" onchange="calcTotal()">
          </div>
        </div>
      </div>

      <div class="total-box">
        <div>
          <div class="total-box-label">Grand Total</div>
          <div>KSh <span id="grand-total">0.00</span></div>
        </div>
        <div style="font-size:36px">🧾</div>
      </div>
      <input type="hidden" name="total" id="total-hidden" value="0">

      <div style="margin-top:16px">
        <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;font-size:15px;justify-content:center">
          ✅ Complete Sale
        </button>
      </div>
      <div style="margin-top:8px">
        <a href="index.php" class="btn btn-outline" style="width:100%;justify-content:center">Cancel</a>
      </div>
    </div>
  </div>
</form>

<script>
window.medicineOptions = `<?= $medOpts ?>`;

// Add first row on load
document.addEventListener('DOMContentLoaded', addSaleRow);

function validateSale() {
  const rows = document.querySelectorAll('#sale-rows tr');
  if (rows.length === 0) { alert('Please add at least one item.'); return false; }
  for (const row of rows) {
    const med = row.querySelector('.med-select')?.value;
    if (!med) { alert('Please select a medicine for all rows.'); return false; }
  }
  return true;
}
</script>

<?php require_once 'includes/footer.php'; ?>
