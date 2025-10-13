<?php
require_once __DIR__ . '/../config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php'); exit;
}

// ambil produk
$stmt = $mysqli->prepare("SELECT id, nama_produk, harga, deskripsi, stok, COALESCE(kategori,'') AS kategori, COALESCE(image,'') AS image FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
if (!$product) { header('Location: index.php'); exit; }

$errors = [];

// ambil daftar kategori
$cats = [];
$r = $mysqli->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($r) { while ($c = $r->fetch_assoc()) $cats[] = $c; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $harga = (float) ($_POST['harga'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $stok = (int) ($_POST['stok'] ?? 0);
    $kategori_select = trim($_POST['kategori_select'] ?? '');
    $kategori_new = trim($_POST['kategori_new'] ?? '');

    if ($nama === '') $errors[] = 'Nama produk wajib diisi.';
    if ($harga <= 0) $errors[] = 'Harga harus lebih dari 0.';

    // handle upload jika ada
    $uploaded_name = null;
    if (!empty($_FILES['image']['name'])) {
      $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
      $maxBytes = 2 * 1024 * 1024; // 2MB
      if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Gagal mengupload gambar.';
      } elseif (!in_array($_FILES['image']['type'], $allowed)) {
        $errors[] = 'Tipe gambar tidak didukung. Gunakan JPG/PNG/GIF/WEBP.';
      } elseif ($_FILES['image']['size'] > $maxBytes) {
        $errors[] = 'Ukuran gambar terlalu besar (max 2MB).';
      } else {
        $uploadsDir = __DIR__ . '/../assets/images/uploads';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $base = bin2hex(random_bytes(8));
        $filename = $base . '.' . $ext;
        $target = $uploadsDir . '/' . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
          $uploaded_name = 'assets/images/uploads/' . $filename;
        } else {
          $errors[] = 'Gagal memindahkan file gambar.';
        }
      }
    }

    if (empty($errors)) {
      $final_category = '';
      if ($kategori_new !== '') {
        $ins = $mysqli->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
        if ($ins) { $ins->bind_param('s', $kategori_new); $ins->execute(); }
        $final_category = $kategori_new;
      } elseif ($kategori_select !== '') {
        $sel = $mysqli->prepare("SELECT name FROM categories WHERE id = ? LIMIT 1");
        if ($sel) { $sel->bind_param('i', $kategori_select); $sel->execute(); $res2 = $sel->get_result(); $row = $res2->fetch_assoc(); $final_category = $row['name'] ?? ''; }
      }

      // jika tidak upload gambar baru, pertahankan yang lama
      $imgToSave = $uploaded_name ?? ($product['image'] ? $product['image'] : null);

      $u = $mysqli->prepare("UPDATE products SET nama_produk = ?, harga = ?, deskripsi = ?, stok = ?, kategori = ?, image = ? WHERE id = ?");
      if ($u) {
        // tipe: nama (s), harga (d), deskripsi (s), stok (i), kategori (s), image (s), id (i)
        $u->bind_param('sdsissi', $nama, $harga, $deskripsi, $stok, $final_category, $imgToSave, $id);
            $u->execute();
            header('Location: index.php'); exit;
        } else {
            $errors[] = 'Gagal mempersiapkan query update: ' . $mysqli->error;
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Produk</title>
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="p-4">
    <div class="container">
      <h1>Edit Produk</h1>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">Nama Produk</label>
          <input type="text" name="nama" class="form-control" value="<?php echo e($product['nama_produk']); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Harga</label>
          <input type="number" step="0.01" name="harga" class="form-control" value="<?php echo e($product['harga']); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Deskripsi</label>
          <textarea name="deskripsi" class="form-control"><?php echo e($product['deskripsi']); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Stok</label>
          <input type="number" name="stok" class="form-control" value="<?php echo e($product['stok']); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Kategori</label>
          <input type="text" name="kategori" class="form-control" value="<?php echo e($product['kategori']); ?>">
        </div>
        <div>
          <button class="btn btn-primary">Simpan</button>
          <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
      </form>
    </div>
  </body>
</html>