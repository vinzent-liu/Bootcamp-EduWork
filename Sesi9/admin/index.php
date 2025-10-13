<?php
require_once __DIR__ . '/../config.php';

// ambil semua produk. Jika kolom kategori tidak ada, fallback ke query tanpa kategori.
$products = [];
$sql_with_cat = "SELECT id, nama_produk, harga, deskripsi, stok, COALESCE(kategori,'') AS kategori FROM products ORDER BY id DESC";
$stmt = $mysqli->prepare($sql_with_cat);
if ($stmt) {
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res) $products = $res->fetch_all(MYSQLI_ASSOC);
} else {
  // fallback: kemungkinan kolom kategori tidak ada
  $sql = "SELECT id, nama_produk, harga, deskripsi, stok FROM products ORDER BY id DESC";
  $stmt2 = $mysqli->prepare($sql);
  if ($stmt2) {
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($res2) {
      $rows = $res2->fetch_all(MYSQLI_ASSOC);
      // add empty kategori field for compatibility with template
      foreach ($rows as $r) {
        $r['kategori'] = '';
        $products[] = $r;
      }
    }
  } else {
    // last resort: run query directly (may throw) - but try to fetch with query()
    $res3 = $mysqli->query($sql);
    if ($res3) {
      while ($row = $res3->fetch_assoc()) {
        $row['kategori'] = '';
        $products[] = $row;
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Products</title>
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="p-4">
    <div class="container">
      <h1>Admin - Produk</h1>
      <p>
        <a href="add_product.php" class="btn btn-primary">Tambah Produk</a>
        <a href="categories.php" class="btn btn-outline-secondary">Kelola Kategori</a>
        <a href="../index.php" class="btn btn-secondary">Lihat Situs</a>
      </p>

      <table class="table table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Kategori</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
          <tr>
            <td><?php echo $p['id']; ?></td>
            <td><?php echo e($p['nama_produk']); ?></td>
            <td>Rp <?php echo number_format($p['harga'],0,',','.'); ?></td>
            <td><?php echo (int)$p['stok']; ?></td>
            <td><?php echo e($p['kategori']); ?></td>
            <td>
              <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
              <a href="delete_product.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus produk ini?');">Hapus</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </body>
</html>