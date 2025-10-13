<?php
require_once __DIR__ . '/../config.php';

$errors = [];

// ambil daftar kategori untuk dropdown
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
    // jika admin mengisi kategori baru, masukkan ke tabel categories
    $final_category = '';
    if ($kategori_new !== '') {
      $ins = $mysqli->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
      if ($ins) { $ins->bind_param('s', $kategori_new); $ins->execute(); }
      $final_category = $kategori_new;
    } elseif ($kategori_select !== '') {
      // jika pilih dari dropdown, dapatkan nama category
      $sel = $mysqli->prepare("SELECT name FROM categories WHERE id = ? LIMIT 1");
      if ($sel) { $sel->bind_param('i', $kategori_select); $sel->execute(); $res = $sel->get_result(); $row = $res->fetch_assoc(); $final_category = $row['name'] ?? ''; }
    }

    // pastikan kolom kategori ada, jika belum tambahkan
    $colCat = $mysqli->query("SHOW COLUMNS FROM products LIKE 'kategori'");
    if ($colCat && $colCat->num_rows === 0) {
      $mysqli->query("ALTER TABLE products ADD COLUMN kategori VARCHAR(100) DEFAULT NULL");
    }
    // pastikan kolom image ada, jika belum tambahkan
    $colCheck = $mysqli->query("SHOW COLUMNS FROM products LIKE 'image'");
    if ($colCheck && $colCheck->num_rows === 0) {
      $mysqli->query("ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL");
    }

    $stmt = $mysqli->prepare("INSERT INTO products (nama_produk, harga, deskripsi, stok, kategori, image) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
      $errors[] = 'Gagal mempersiapkan query: ' . $mysqli->error;
    } else {
      $imgParam = $uploaded_name ?? null;
      // tipe: nama(s), harga(d), deskripsi(s), stok(i), kategori(s), image(s)
      $stmt->bind_param('sdsiss', $nama, $harga, $deskripsi, $stok, $final_category, $imgParam);
      $stmt->execute();
      header('Location: index.php');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Produk</title>
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="p-4">
    <div class="container">
      <h1>Tambah Produk</h1>
      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?>
        </div>
      <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">Nama Produk</label>
          <input type="text" name="nama" class="form-control" value="<?php echo isset($nama) ? e($nama) : ''; ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Harga</label>
          <input type="number" step="0.01" name="harga" class="form-control" value="<?php echo isset($harga) ? e($harga) : ''; ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Deskripsi</label>
          <textarea name="deskripsi" class="form-control"><?php echo isset($deskripsi) ? e($deskripsi) : ''; ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Stok</label>
          <input type="number" name="stok" class="form-control" value="<?php echo isset($stok) ? e($stok) : 0; ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Kategori</label>
          <input type="text" name="kategori" class="form-control" value="<?php echo isset($kategori) ? e($kategori) : ''; ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Gambar (opsional)</label>
          <input type="file" name="image" accept="image/*" class="form-control">
        </div>
        <div>
          <button class="btn btn-primary">Simpan</button>
          <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
      </form>
    </div>
  </body>
</html>