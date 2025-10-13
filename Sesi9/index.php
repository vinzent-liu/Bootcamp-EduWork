<?php
require_once __DIR__ . '/config.php';

// Tangani query pencarian (server-side) dan tampilan bergrup (1 produk per kategori)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';
$products = [];

// Jika kategori dipilih, tampilkan semua produk dalam kategori tersebut
if ($categoryFilter !== '') {
  $stmt = $mysqli->prepare("SELECT id, nama_produk, harga, deskripsi, stok, COALESCE(kategori,'') AS kategori FROM products WHERE COALESCE(kategori,'') = ? ORDER BY id DESC");
  if ($stmt) {
    $stmt->bind_param('s', $categoryFilter);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
  }

} elseif ($q !== '') {
  // Coba pencarian termasuk kategori jika tersedia; jika gagal, gunakan fallback tanpa kategori
  $like = "%{$q}%";
  $search_sqls = [
    "SELECT id, nama_produk, harga, deskripsi, stok, COALESCE(kategori, '') AS kategori FROM products WHERE nama_produk LIKE ? OR deskripsi LIKE ? OR COALESCE(kategori,'') LIKE ? ORDER BY id DESC",
  // fallback jika kolom kategori tidak ada
    "SELECT id, nama_produk, harga, deskripsi, stok, '' AS kategori FROM products WHERE nama_produk LIKE ? OR deskripsi LIKE ? ORDER BY id DESC"
  ];

  $stmt = false;
  foreach ($search_sqls as $sql) {
    $stmt = $mysqli->prepare($sql);
    if ($stmt) break;
  }
  if ($stmt) {
  // bind dua atau tiga parameter tergantung statement
    $param_count = count($stmt->param_count ? range(1, $stmt->param_count) : []);
  // cara mudah: coba bind 3 param; jika statement mengharapkan 2, deteksi lewat SQL dan bind sesuai
    if (strpos($sql, "COALESCE(kategori") !== false) {
      $stmt->bind_param('sss', $like, $like, $like);
    } else {
      $stmt->bind_param('ss', $like, $like);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    } else {
  // Jika prepare gagal untuk semua varian, fallback untuk memilih semua
    $stmt = $mysqli->prepare("SELECT id, nama_produk, harga, deskripsi, stok, '' AS kategori FROM products ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
  }

} else {
  // Tanpa pencarian/ filter: tampilkan semua produk (urutan terbaru terlebih dahulu)
  $stmt = $mysqli->prepare("SELECT id, nama_produk, harga, deskripsi, stok, COALESCE(kategori,'') AS kategori FROM products ORDER BY id DESC");
  if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
  } else {
    // fallback: jika prepare gagal, coba query langsung
    $res2 = $mysqli->query("SELECT id, nama_produk, harga, deskripsi, stok FROM products ORDER BY id DESC");
    if ($res2) {
      while ($r = $res2->fetch_assoc()) {
        $r['kategori'] = '';
        $products[] = $r;
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
    <title>Simple E-commerce</title>
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
            <input id="offcanvas-search-input" type="search" name="q" class="form-control" placeholder="Cari produk atau kategori..." value="<?php echo e($q); ?>">
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
      <div class="site-hero mb-4 p-4 d-flex align-items-center justify-content-between">
        <div>
          <h1 class="mb-1">Marketplace Demo</h1>
          <p class="muted mb-0">Temukan dan bandingkan produk terbaik dari berbagai kategori.</p>
        </div>
        <div class="text-end">
          <a class="btn btn-success" href="cart.php">Lihat Keranjang (<?php echo $cart_count; ?>)</a>
        </div>
      </div>

      <!-- inline search removed (use navbar offcanvas search) -->
      <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($products as $p): ?>
        <div class="col">
          <div class="card h-100">
            <?php
              // prepare up to 3 images: uploaded image (if any) + 2 unsplash fallbacks
              $imgs = [];
              if (!empty($p['image'])) $imgs[] = $p['image'];
              $imgs[] = 'https://source.unsplash.com/640x480/?' . urlencode($p['nama_produk']);
              $imgs[] = 'https://source.unsplash.com/640x480/?' . urlencode($p['kategori'] ?: $p['nama_produk']);
            ?>
            <div id="carousel-<?php echo $p['id']; ?>" class="product-carousel carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner">
                <?php foreach ($imgs as $i => $imgUrl): ?>
                  <div class="carousel-item <?php echo $i===0 ? 'active' : ''; ?>">
                    <img src="assets/images/default.png" data-unsplash="<?php echo $imgUrl; ?>" class="d-block w-100 card-img-top" alt="<?php echo e($p['nama_produk']); ?>">
                  </div>
                <?php endforeach; ?>
              </div>
              <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?php echo $p['id']; ?>" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?php echo $p['id']; ?>" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
              </button>
            </div>

            <div class="card-body">
              <h5 class="card-title"><?php echo e($p['nama_produk']); ?>
                <?php if (!empty($p['kategori'])): ?>
                  <span class="badge badge-category ms-2"><?php echo e($p['kategori']); ?></span>
                <?php endif; ?>
              </h5>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                  <p class="card-text text-muted mb-0">Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?></p>
                  <small class="muted small">Stok: <?php echo (int)$p['stok']; ?></small>
                </div>
                <div class="seller-badge text-end">
                  <small class="d-block">Toko</small>
                  <strong>Penjual Lokal</strong>
                </div>
              </div>
              <p class="card-text small"><?php echo e(substr($p['deskripsi'], 0, 120)); ?></p>
              <a href="product.php?id=<?php echo $p['id']; ?>" class="btn btn-primary">Lihat Detail</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php require __DIR__ . '/partials/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
  </body>
</html>
