<?php
require_once __DIR__ . '/config.php';

// Perbarui jumlah atau hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        foreach ($_POST['quantities'] as $index => $q) {
            $q = max(0, (int)$q);
            if ($q === 0) {
                unset($_SESSION['cart'][$index]);
            } else {
                $_SESSION['cart'][$index]['quantity'] = $q;
            }
        }
  // reindex (atur ulang index array)
        $_SESSION['cart'] = array_values($_SESSION['cart'] ?? []);
    }
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$total = 0;
foreach ($cart as $item) {
    $total += $item['harga'] * $item['quantity'];
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keranjang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
  </head>
  <body>
    <?php require __DIR__ . '/partials/header.php'; ?>

    <!-- Offcanvas Search -->
    <div class="offcanvas offcanvas-top" tabindex="-1" id="offcanvasSearch" aria-labelledby="offcanvasSearchLabel">
      <div class="offcanvas-header">
        <h5 id="offcanvasSearchLabel">Cari produk</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body p-4">
        <form method="get" action="index.php">
          <div class="input-group">
            <input id="offcanvas-search-input" type="search" name="q" class="form-control" placeholder="Cari produk atau kategori..." value="">
            <button class="btn btn-primary" type="submit">Cari</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Offcanvas Menu -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
      <div class="offcanvas-header">
        <h5 id="offcanvasMenuLabel">Menu</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <ul class="list-unstyled">
          <li><a href="index.php" class="d-block py-2">Beranda</a></li>
          <li><a href="index.php" class="d-block py-2">Produk</a></li>
          <li><a href="cart.php" class="d-block py-2">Keranjang</a></li>
          <li><a href="checkout.php" class="d-block py-2">Checkout</a></li>
        </ul>
      </div>
    </div>

    <div class="container py-4">
  <a href="index.php">&larr; Lanjut Berbelanja</a>
  <h1 class="mt-3">Keranjang Belanja</h1>
      <?php if (empty($cart)): ?>
        <div class="card p-4">
          <p class="mb-0">Keranjang kosong.</p>
        </div>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="action" value="update">
          <div class="card p-3 mb-3">
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($cart as $i => $it): ?>
                  <tr>
                    <td><?php echo e($it['nama_produk']); ?></td>
                    <td>Rp <?php echo number_format($it['harga'], 0, ',', '.'); ?></td>
                    <td style="width:140px;"><input type="number" name="quantities[<?php echo $i; ?>]" value="<?php echo (int)$it['quantity']; ?>" min="0" class="form-control"></td>
                    <td>Rp <?php echo number_format($it['harga'] * $it['quantity'], 0, ',', '.'); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <button class="btn btn-secondary" type="submit">Update Keranjang</button>
              </div>
              <div>
                <strong>Total: Rp <?php echo number_format($total, 0, ',', '.'); ?></strong>
                <a href="checkout.php" class="btn btn-primary ms-3">Lanjutkan ke Checkout</a>
              </div>
            </div>
          </div>
        </form>
      <?php endif; ?>
      <?php require __DIR__ . '/partials/footer.php'; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
