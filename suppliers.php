<?php

require_once 'includes/header.php';
$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $name    = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact_person'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssss',$name,$contact,$phone,$email,$address);
            $stmt->execute();
            $msg = 'Supplier added successfully.';
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE suppliers SET name=?,contact_person=?,phone=?,email=?,address=? WHERE id=?");
            $stmt->bind_param('sssssi',$name,$contact,$phone,$email,$address,$id);
            $stmt->execute();
            $msg = 'Supplier updated.';
        }
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM suppliers WHERE id=$id");
        $msg = 'Supplier deleted.';
    }
}

$suppliers = $db->query("
  SELECT s.*, COUNT(m.id) as med_count
  FROM suppliers s LEFT JOIN medicines m ON m.supplier_id=s.id AND m.status='active'
  GROUP BY s.id ORDER BY s.name
");
$editS = isset($_GET['edit']) ? $db->query("SELECT * FROM suppliers WHERE id=".(int)$_GET['edit'])->fetch_assoc() : null;
?>
<div class="page-header">
  <div class="page-title">Suppliers</div>
  <button class="btn btn-primary" onclick="toggleForm('sup-form')">⊕ Add Supplier</button>
</div>
<?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

<div id="sup-form" class="card" style="display:<?= $editS?'block':'none' ?>;margin-bottom:20px">
  <div class="card-header"><div class="card-title"><?= $editS?'Edit':'Add' ?> Supplier</div>
    <button class="btn btn-outline btn-sm" onclick="toggleForm('sup-form')">✕</button></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="<?= $editS?'edit':'add' ?>">
      <?php if($editS): ?><input type="hidden" name="id" value="<?= $editS['id'] ?>"><?php endif; ?>
      <div class="form-grid">
        <div class="form-group"><label>Company Name *</label><input name="name" required value="<?= htmlspecialchars($editS['name']??'') ?>"></div>
        <div class="form-group"><label>Contact Person</label><input name="contact_person" value="<?= htmlspecialchars($editS['contact_person']??'') ?>"></div>
        <div class="form-group"><label>Phone</label><input name="phone" value="<?= htmlspecialchars($editS['phone']??'') ?>"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($editS['email']??'') ?>"></div>
        <div class="form-group full"><label>Address</label><textarea name="address"><?= htmlspecialchars($editS['address']??'') ?></textarea></div>
      </div>
      <div style="margin-top:12px"><button type="submit" class="btn btn-primary">💾 Save</button></div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Suppliers</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Company</th><th>Contact Person</th><th>Phone</th><th>Email</th><th>Medicines</th><th>Actions</th></tr></thead>
      <tbody>
      <?php while($r = $suppliers->fetch_assoc()): ?>
      <tr>
        <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
        <td><?= htmlspecialchars($r['contact_person']??'—') ?></td>
        <td style="font-family:var(--mono)"><?= htmlspecialchars($r['phone']??'—') ?></td>
        <td style="font-size:12px"><?= htmlspecialchars($r['email']??'—') ?></td>
        <td><span class="tag tag-info"><?= $r['med_count'] ?> items</span></td>
        <td>
          <a href="suppliers.php?edit=<?= $r['id'] ?>" class="btn btn-outline btn-sm">✏ Edit</a>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this supplier?">✕</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<script>function toggleForm(id){const f=document.getElementById(id);f.style.display=f.style.display==='none'?'block':'none';}</script>
<?php require_once 'includes/footer.php'; ?>
