<?php

require_once 'includes/header.php';
$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?,?)");
            $stmt->bind_param('ss', $name, $desc);
            $stmt->execute();
            $msg = 'Category added.';
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
            $stmt->bind_param('ssi', $name, $desc, $id);
            $stmt->execute();
            $msg = 'Category updated.';
        }
    }
    if ($action === 'delete') {
        $db->query("DELETE FROM categories WHERE id=".(int)$_POST['id']);
        $msg = 'Category deleted.';
    }
}

$cats = $db->query("SELECT c.*, COUNT(m.id) as med_count FROM categories c LEFT JOIN medicines m ON m.category_id=c.id AND m.status='active' GROUP BY c.id ORDER BY c.name");
$editC = isset($_GET['edit']) ? $db->query("SELECT * FROM categories WHERE id=".(int)$_GET['edit'])->fetch_assoc() : null;
?>
<div class="page-header">
  <div class="page-title">Categories</div>
  <button class="btn btn-primary" onclick="toggleForm('cat-form')">⊕ Add Category</button>
</div>
<?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

<div id="cat-form" class="card" style="display:<?= $editC?'block':'none' ?>;margin-bottom:20px">
  <div class="card-header"><div class="card-title"><?= $editC?'Edit':'Add' ?> Category</div>
    <button class="btn btn-outline btn-sm" onclick="toggleForm('cat-form')">✕</button></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="<?= $editC?'edit':'add' ?>">
      <?php if($editC): ?><input type="hidden" name="id" value="<?= $editC['id'] ?>"><?php endif; ?>
      <div class="form-grid">
        <div class="form-group"><label>Category Name *</label><input name="name" required value="<?= htmlspecialchars($editC['name']??'') ?>"></div>
        <div class="form-group full"><label>Description</label><textarea name="description"><?= htmlspecialchars($editC['description']??'') ?></textarea></div>
      </div>
      <div style="margin-top:12px"><button type="submit" class="btn btn-primary">💾 Save</button></div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Medicine Categories</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Description</th><th>Medicines</th><th>Actions</th></tr></thead>
      <tbody>
      <?php while($r = $cats->fetch_assoc()): ?>
      <tr>
        <td style="font-family:var(--mono);color:var(--text-mute)"><?= $r['id'] ?></td>
        <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
        <td style="color:var(--text-mute);font-size:13px"><?= htmlspecialchars($r['description']??'—') ?></td>
        <td><span class="tag tag-info"><?= $r['med_count'] ?> medicines</span></td>
        <td>
          <a href="categories.php?edit=<?= $r['id'] ?>" class="btn btn-outline btn-sm">✏ Edit</a>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete category?">✕</button>
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
