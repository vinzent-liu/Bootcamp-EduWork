<?php
require_once __DIR__ . '/config.php';

// Handle search query (server-side) and grouped display (1 product per kategori)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';
$products = [];

// If category selected, show all products in that category
if ($categoryFilter !== '') {
  $stmt = $mysqli->prepare("SELECT id, nama_produk, harga, deskripsi, stok, COALESCE(kategori,'') AS kategori FROM products WHERE COALESCE(kategori,'') = ? ORDER BY id DESC");
  if ($stmt) {
    $stmt->bind_param('s', $categoryFilter);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
  }

} elseif ($q !== '') {
  // Try search including kategori if available; if that fails, fallback to search without kategori
  $like = "%{$q}%";
  $search_sqls = [
    "SELECT id, nama_produk, harga, deskripsi, stok, COALESCE(kategori, '') AS kategori FROM products WHERE nama_produk LIKE ? OR deskripsi LIKE ? OR COALESCE(kategori,'') LIKE ? ORDER BY id DESC",
    // fallback if kategori column doesn't exist
    "SELECT id, nama_produk, harga, deskripsi, stok, '' AS kategori FROM products WHERE nama_produk LIKE ? OR deskripsi LIKE ? ORDER BY id DESC"
  ];

  $stmt = false;
  foreach ($search_sqls as $sql) {
    $stmt = $mysqli->prepare($sql);
    if ($stmt) break;
  }
  if ($stmt) {
    // bind first two or three params depending on statement
    $param_count = count($stmt->param_count ? range(1, $stmt->param_count) : []);
    // easier: try bind 3 params, if statement expects 2 it'll ignore extras in mysqli (bind_param requires exact), so detect by checking SQL
    if (strpos($sql, "COALESCE(kategori") !== false) {
      $stmt->bind_param('sss', $like, $like, $like);
    } else {
      $stmt->bind_param('ss', $like, $like);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    } else {
    // If prepare failed for all variants, fallback to selecting all
    $stmt = $mysqli->prepare("SELECT id, nama_produk, harga, deskripsi, stok, '' AS kategori FROM products ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
  }

} else {
  // No search: attempt to fetch one product per kategori (latest id per kategori)
  $grouped_sql = "SELECT p.id, p.nama_produk, p.harga, p.deskripsi, p.stok, COALESCE(p.kategori, '') AS kategori
    FROM products p
    JOIN (
      SELECT COALESCE(kategori,'') AS kategori, MAX(id) AS maxid
      FROM products
      GROUP BY COALESCE(kategori,'')
    ) m ON COALESCE(p.kategori,'') = m.kategori AND p.id = m.maxid";

  $stmt = $mysqli->prepare($grouped_sql);
  if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
  } else {
    // fallback if kategori column doesn't exist or query fails: show all products
    $stmt = $mysqli->prepare("SELECT id, nama_produk, harga, deskripsi, stok, '' AS kategori FROM products ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
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
    <?php
      // fetch categories for dropdown
      $cats = [];
      $cat_stmt = $mysqli->prepare("SELECT DISTINCT COALESCE(kategori,'') AS kategori FROM products ORDER BY kategori ASC");
      if ($cat_stmt) {
        $cat_stmt->execute();
        $res = $cat_stmt->get_result();
        while ($r = $res->fetch_assoc()) {
          if ($r['kategori'] !== '') $cats[] = $r['kategori'];
        }
      }
    ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
      <div class="container">
        <a class="navbar-brand" href="index.php">My Shop</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="catDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Kategori</a>
              <ul class="dropdown-menu" aria-labelledby="catDropdown">
                <?php if (empty($cats)): ?>
                  <li><a class="dropdown-item" href="#">Semua</a></li>
                <?php else: ?>
                  <?php foreach ($cats as $c): ?>
                    <li><a class="dropdown-item" href="index.php?category=<?php echo urlencode($c); ?>"><?php echo e($c); ?></a></li>
                  <?php endforeach; ?>
                <?php endif; ?>
              </ul>
            </li>
          </ul>

          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSearch" aria-controls="offcanvasSearch">Cari</button>
            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu" aria-controls="offcanvasMenu">Menu</button>
          </div>
        </div>
      </div>
    </nav>

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
          <h1 class="mb-1">Simple E-commerce</h1>
          <p class="muted mb-0">Belanja produk pilihan dengan cepat dan aman.</p>
        </div>
        <div class="text-end">
          <a class="btn btn-primary" href="cart.php">Cart (<?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0; ?>)</a>
        </div>
      </div>

      <!-- inline search removed (use navbar offcanvas search) -->
      <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($products as $p): ?>
        <div class="col">
          <div class="card h-100">
            <?php
              // use Unsplash source as product image fallback; search by product name
              $unsplash = 'https://source.unsplash.com/640x480/?' . urlencode($p['nama_produk']);
            ?>
            <img src="assets/images/default.png" data-unsplash="<?php echo $unsplash; ?>" class="card-img-top" alt="<?php echo e($p['nama_produk']); ?>" loading="lazy">
            <div class="card-body">
              <h5 class="card-title"><?php echo e($p['nama_produk']); ?>
                <?php if (!empty($p['kategori'])): ?>
                  <span class="badge badge-category ms-2"><?php echo e($p['kategori']); ?></span>
                <?php endif; ?>
              </h5>
              <p class="card-text text-muted">Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?></p>
              <p class="card-text small"><?php echo e(substr($p['deskripsi'], 0, 100)); ?></p>
              <a href="product.php?id=<?php echo $p['id']; ?>" class="btn btn-primary">Lihat Detail</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <footer class="site-footer text-center">
        <div class="container">
          <small>© <?php echo date('Y'); ?> My Shop — Simple demo e-commerce.</small>
        </div>
      </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
  </body>
</html>
