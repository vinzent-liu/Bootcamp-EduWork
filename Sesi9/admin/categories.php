<?php
require_once __DIR__ . '/../config.php';

// pastikan tabel categories ada
$check = $mysqli->query("SHOW TABLES LIKE 'categories'");
if (!$check || $check->num_rows === 0) {
    $mysqli->query("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$errors = [];
// add category
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') $errors[] = 'Nama kategori kosong.';
    if (empty($errors)) {
        $stmt = $mysqli->prepare("INSERT INTO categories (name) VALUES (?)");
        if ($stmt) {
            $stmt->bind_param('s', $name);
            if (!$stmt->execute()) $errors[] = 'Gagal menambah kategori: ' . $stmt->error;
            else header('Location: categories.php');
        } else {
            $errors[] = 'Gagal mempersiapkan query: ' . $mysqli->error;
        }
    }
}

// edit category
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($id <= 0 || $name === '') $errors[] = 'ID atau nama tidak valid.';
    if (empty($errors)) {
        $u = $mysqli->prepare("UPDATE categories SET name = ? WHERE id = ?");
        if ($u) {
            $u->bind_param('si', $name, $id);
            if (!$u->execute()) $errors[] = 'Gagal update: ' . $u->error;
            else header('Location: categories.php');
        } else {
            $errors[] = 'Gagal mempersiapkan query: ' . $mysqli->error;
        }
    }
}

// delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id > 0) {
        // ambil nama category untuk dibersihkan di products
        $r = $mysqli->prepare("SELECT name FROM categories WHERE id = ?");
        $r->bind_param('i', $id);
        $r->execute();
        $res = $r->get_result();
        $row = $res->fetch_assoc();
        $catname = $row['name'] ?? null;
        $d = $mysqli->prepare("DELETE FROM categories WHERE id = ?");
        $d->bind_param('i', $id);
        $d->execute();
        if ($catname) {
            // bersihkan products yang memakai kategori ini (set '')
            $s = $mysqli->prepare("UPDATE products SET kategori = NULL WHERE kategori = ?");
            if ($s) { $s->bind_param('s', $catname); $s->execute(); }
        }
        header('Location: categories.php'); exit;
    }
}

// ambil semua kategori
$cats = [];
$res = $mysqli->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($res) {
    while ($c = $res->fetch_assoc()) $cats[] = $c;
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Kategori</title>
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="p-4">
    <div class="container">
      <h1>Kelola Kategori</h1>
      <p><a href="index.php" class="btn btn-secondary">Kembali ke Produk</a></p>

      <?php if ($errors): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>' . e($e) . '</div>'; ?></div>
      <?php endif; ?>

      <div class="card p-3 mb-3">
        <form method="post" class="row g-2 align-items-center">
          <input type="hidden" name="action" value="add">
          <div class="col-auto flex-grow-1">
            <input type="text" name="name" class="form-control" placeholder="Nama kategori baru">
          </div>
          <div class="col-auto">
            <button class="btn btn-primary">Tambah</button>
          </div>
        </form>
      </div>

      <table class="table table-bordered">
        <thead>
          <tr><th>ID</th><th>Nama</th><th>Aksi</th></tr>
        </thead>
        <tbody>
          <?php foreach ($cats as $c): ?>
          <tr>
            <td><?php echo $c['id']; ?></td>
            <td><?php echo e($c['name']); ?></td>
            <td>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $c['id']; ?>">Edit</button>
              <a href="?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus kategori ini? Produk yang terkait akan dikosongkan.');">Hapus</a>

              <!-- modal edit -->
              <div class="modal fade" id="editModal<?php echo $c['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="post">
                      <div class="modal-header"><h5 class="modal-title">Edit Kategori</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                        <div class="mb-3">
                          <label class="form-label">Nama</label>
                          <input type="text" name="name" class="form-control" value="<?php echo e($c['name']); ?>">
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>