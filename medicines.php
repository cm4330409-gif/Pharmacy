<?php
require_once 'includes/header.php';
$db = getDB();

// ── Handle actions
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $fields = ['name','generic_name','category_id','supplier_id','batch_number','unit',
                   'purchase_price','selling_price','stock_quantity','min_stock_level',
                   'expiry_date','manufacture_date','description','status'];
        $data = [];
        foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '');

        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO medicines (name,generic_name,category_id,supplier_id,batch_number,unit,purchase_price,selling_price,stock_quantity,min_stock_level,expiry_date,manufacture_date,description,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('ssiissddiiisss',
                $data['name'],$data['generic_name'],$data['category_id'],$data['supplier_id'],
                $data['batch_number'],$data['unit'],$data['purchase_price'],$data['selling_price'],
                $data['stock_quantity'],$data['min_stock_level'],$data['expiry_date'],
                $data['manufacture_date'],$data['description'],$data['status']);
            $stmt->execute();
            $msg = 'Medicine added successfully.';
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare("UPDATE medicines SET name=?,generic_name=?,category_id=?,supplier_id=?,batch_number=?,unit=?,purchase_price=?,selling_price=?,stock_quantity=?,min_stock_level=?,expiry_date=?,manufacture_date=?,description=?,status=? WHERE id=?");
            $stmt->bind_param('ssiissddiiisssi',
                $data['name'],$data['generic_name'],$data['category_id'],$data['supplier_id'],
                $data['batch_number'],$data['unit'],$data['purchase_price'],$data['selling_price'],
                $data['stock_quantity'],$data['min_stock_level'],$data['expiry_date'],
                $data['manufacture_date'],$data['description'],$data['status'],$id);
            $stmt->execute();
            $msg = 'Medicine updated successfully.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->query("UPDATE medicines SET status='discontinued' WHERE id=$id");
        $msg = 'Medicine marked as discontinued.';
    }
}

// ── Filters
$search = trim($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$page  = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = "WHERE m.status != 'discontinued'";
if ($search) $where .= " AND (m.name LIKE '%".addslashes($search)."%' OR m.generic_name LIKE '%".addslashes($search)."%' OR m.batch_number LIKE '%".addslashes($search)."%')";
if ($catFilter) $where .= " AND m.category_id = $catFilter";

$total = $db->query("SELECT COUNT(*) FROM medicines m $where")->fetch_row()[0];
$pages = ceil($total / $perPage);

$meds = $db->query("
  SELECT m.*, c.name as cat_name, s.name as sup_name,
    DATEDIFF(m.expiry_date, CURDATE()) as days_to_expiry
  FROM medicines m
  LEFT JOIN categories c ON c.id = m.category_id
  LEFT JOIN suppliers s ON s.id = m.supplier_id
  $where ORDER BY m.name ASC LIMIT $perPage OFFSET $offset
");

$categories = $db->query("SELECT * FROM categories ORDER BY name");
$suppliers  = $db->query("SELECT * FROM suppliers ORDER BY name");
$catArr = []; $supArr = [];
while ($r = $categories->fetch_assoc()) $catArr[] = $r;
while ($r = $suppliers->fetch_assoc()) $supArr[] = $r;
$categories->data_seek(0); // reset

// Edit mode
$editMed = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $editMed = $db->query("SELECT * FROM medicines WHERE id=$eid")->fetch_assoc();
}
?>

<div class="page-header">
  <div>
    <div class="page-title">Medicines</div>
    <div class="page-subtitle"><?= $total ?> active records</div>
  </div>
  <button class="btn btn-primary" onclick="toggleForm('add-form')">⊕ Add Medicine</button>
</div>

<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

<!-- Add/Edit Form -->
<div id="add-form" class="card" style="display:<?= ($editMed || isset($_POST['action'])) ? 'block':'none' ?>;margin-bottom:20px">
  <div class="card-header">
    <div class="card-title"><?= $editMed ? 'Edit Medicine' : 'Add New Medicine' ?></div>
    <button class="btn btn-outline btn-sm" onclick="toggleForm('add-form')">✕ Cancel</button>
  </div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="<?= $editMed ? 'edit' : 'add' ?>">
      <?php if($editMed): ?><input type="hidden" name="id" value="<?= $editMed['id'] ?>"><?php endif; ?>
      <div class="form-grid">
        <div class="form-group">
          <label>Medicine Name *</label>
          <input name="name" required value="<?= htmlspecialchars($editMed['name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Generic Name</label>
          <input name="generic_name" value="<?= htmlspecialchars($editMed['generic_name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category_id">
            <option value="">-- None --</option>
            <?php foreach($catArr as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($editMed['category_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Supplier</label>
          <select name="supplier_id">
            <option value="">-- None --</option>
            <?php foreach($supArr as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($editMed['supplier_id']??'')==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Batch Number</label>
          <input name="batch_number" value="<?= htmlspecialchars($editMed['batch_number'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Unit</label>
          <select name="unit">
            <?php foreach(['Tablet','Capsule','Syrup','Bottle','Injection','Inhaler','Cream','Drops','Sachet','Other'] as $u): ?>
            <option <?= ($editMed['unit']??'Tablet')===$u?'selected':'' ?>><?= $u ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Purchase Price (KSh) *</label>
          <input type="number" name="purchase_price" step="0.01" min="0" required value="<?= $editMed['purchase_price'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label>Selling Price (KSh) *</label>
          <input type="number" name="selling_price" step="0.01" min="0" required value="<?= $editMed['selling_price'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label>Stock Quantity *</label>
          <input type="number" name="stock_quantity" min="0" required value="<?= $editMed['stock_quantity'] ?? 0 ?>">
        </div>
        <div class="form-group">
          <label>Min Stock Level</label>
          <input type="number" name="min_stock_level" min="0" value="<?= $editMed['min_stock_level'] ?? 10 ?>">
        </div>
        <div class="form-group">
          <label>Expiry Date *</label>
          <input type="date" name="expiry_date" required value="<?= $editMed['expiry_date'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label>Manufacture Date</label>
          <input type="date" name="manufacture_date" value="<?= $editMed['manufacture_date'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="active" <?= ($editMed['status']??'active')==='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= ($editMed['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
          </select>
        </div>
        <div class="form-group full">
          <label>Description</label>
          <textarea name="description"><?= htmlspecialchars($editMed['description'] ?? '') ?></textarea>
        </div>
      </div>
      <div style="margin-top:16px;display:flex;gap:10px">
        <button type="submit" class="btn btn-primary">💾 Save Medicine</button>
        <button type="button" class="btn btn-outline" onclick="toggleForm('add-form')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Filter Bar -->
<div class="card">
  <div class="card-header">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <input name="q" placeholder="Search name / generic / batch…" value="<?= htmlspecialchars($search) ?>" style="width:260px">
      <select name="cat" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach($catArr as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $catFilter==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
      <?php if($search||$catFilter): ?><a href="medicines.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>#</th><th>Name</th><th>Generic</th><th>Category</th><th>Unit</th>
        <th>Stock</th><th>Sell Price</th><th>Expiry</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php while ($r = $meds->fetch_assoc()):
        $days = (int)$r['days_to_expiry'];
        $expClass = $days < 0 ? 'tag-danger' : ($days <= 30 ? 'tag-danger' : ($days <= 60 ? 'tag-warn' : 'tag-ok'));
        $expLabel = $days < 0 ? 'Expired' : ($days === 0 ? 'Today' : "$days d");
        $stockOk  = $r['stock_quantity'] > $r['min_stock_level'];
      ?>
      <tr>
        <td style="font-family:var(--mono);font-size:11px;color:var(--text-mute)"><?= $r['id'] ?></td>
        <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
        <td style="color:var(--text-mute);font-size:12px"><?= htmlspecialchars($r['generic_name']) ?></td>
        <td><?= htmlspecialchars($r['cat_name'] ?? '—') ?></td>
        <td><?= $r['unit'] ?></td>
        <td>
          <span class="<?= $stockOk ? 'tag tag-ok' : 'tag tag-danger' ?>"><?= $r['stock_quantity'] ?></span>
        </td>
        <td style="font-family:var(--mono)">KSh <?= number_format($r['selling_price'],2) ?></td>
        <td>
          <span class="tag <?= $expClass ?>"><?= date('d/m/Y', strtotime($r['expiry_date'])) ?></span>
          <small style="display:block;color:var(--text-mute);font-size:11px"><?= $expLabel ?></small>
        </td>
        <td><span class="tag <?= $r['status']==='active' ? 'tag-ok' : 'tag-neutral' ?>"><?= $r['status'] ?></span></td>
        <td>
          <a href="medicines.php?edit=<?= $r['id'] ?>" class="btn btn-outline btn-sm">✏ Edit</a>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Discontinue this medicine?">✕</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
      <?php if ($total === 0): ?>
      <tr><td colspan="10" style="text-align:center;padding:32px;color:var(--text-mute)">No medicines found. <a href="medicines.php" style="color:var(--emerald)">Clear filters</a></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div style="padding:16px 20px">
    <div class="pagination">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>&cat=<?= $catFilter ?>" 
           class="<?= $i === $page ? 'active-page' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function toggleForm(id) {
  const f = document.getElementById(id);
  f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>
