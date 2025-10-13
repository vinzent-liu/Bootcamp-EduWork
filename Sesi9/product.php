<?php
require_once __DIR__ . '/config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $mysqli->prepare("SELECT id, nama_produk, harga, deskripsi, stok FROM products WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
if (!$product) {
    echo "Product not found";
    exit;
}

// Tangani penambahan ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;
  // inisialisasi keranjang
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $product['id']) {
            $item['quantity'] += $qty;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['cart'][] = [
            'id' => $product['id'],
            'nama_produk' => $product['nama_produk'],
            'harga' => $product['harga'],
            'quantity' => $qty
        ];
    }
    header('Location: cart.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($product['nama_produk']); ?></title>
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
      <a href="index.php">&larr; Kembali</a>
      <div class="row mt-3">
        <div class="col-md-6">
          <?php
            $prodImgs = [];
            if (!empty($product['image'])) $prodImgs[] = $product['image'];
            $prodImgs[] = 'https://source.unsplash.com/800x600/?' . urlencode($product['nama_produk']);
            $prodImgs[] = 'https://source.unsplash.com/800x600/?' . urlencode($product['nama_produk'] . ' product');
          ?>
          <div class="card">
            <div id="productGallery" class="carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner">
                <?php foreach ($prodImgs as $i => $u): ?>
                  <div class="carousel-item <?php echo $i===0 ? 'active' : ''; ?>">
                    <img src="assets/images/default.png" data-unsplash="<?php echo $u; ?>" class="d-block w-100" alt="<?php echo e($product['nama_produk']); ?>">
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="card-body">
              <h2 class="h4 mb-1"><?php echo e($product['nama_produk']); ?></h2>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                  <span class="h5 text-primary">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></span>
                  <small class="muted d-block">Stok: <?php echo (int)$product['stok']; ?></small>
                </div>
                <div class="seller-badge text-end">
                  <small class="d-block">Penjual</small>
                  <strong>Penjual Lokal</strong>
                </div>
              </div>
              <p class="mb-3"><?php echo e($product['deskripsi']); ?></p>

              <form method="post" class="d-flex gap-2 align-items-center">
                <input type="number" name="quantity" value="1" min="1" max="<?php echo (int)$product['stok']; ?>" class="form-control w-25">
                <button class="btn btn-success">Tambah ke Keranjang</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php require __DIR__ . '/partials/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
