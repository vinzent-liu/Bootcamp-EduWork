<?php
// Partial: header/navbar + offcanvas search/menu
// Assumes config.php already di-include di halaman yang memanggil partial ini

// ambil kategori (prioritaskan tabel categories jika ada)
$cats = [];
$res = $mysqli->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($res && $res->num_rows > 0) {
    while ($r = $res->fetch_assoc()) $cats[] = $r['name'];
} else {
    $cat_stmt = $mysqli->prepare("SELECT DISTINCT COALESCE(kategori,'') AS kategori FROM products ORDER BY kategori ASC");
    if ($cat_stmt) {
        $cat_stmt->execute();
        $res2 = $cat_stmt->get_result();
        while ($r = $res2->fetch_assoc()) if ($r['kategori'] !== '') $cats[] = $r['kategori'];
    }
}

$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="/e_commerce/index.php">
      <img src="/e_commerce/assets/images/default.png" alt="logo" style="width:36px;height:36px;object-fit:cover;border-radius:6px;margin-right:8px"> 
      <strong>e-Commerce</strong>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="catDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Kategori</a>
          <ul class="dropdown-menu" aria-labelledby="catDropdown">
            <li><a class="dropdown-item" href="/e_commerce/index.php">Semua</a></li>
            <?php foreach ($cats as $c): ?>
              <li><a class="dropdown-item" href="/e_commerce/index.php?category=<?php echo urlencode($c); ?>"><?php echo e($c); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
      </ul>

      <form class="d-flex mx-auto" method="get" action="/e_commerce/index.php" style="max-width:640px;width:100%">
        <div class="input-group w-100">
          <input id="global-search-input" name="q" value="<?php echo e($_GET['q'] ?? ''); ?>" type="search" class="form-control" placeholder="Cari produk, kategori, atau toko...">
          <button class="btn btn-success" type="submit">Cari</button>
        </div>
      </form>

      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-light position-relative" href="/e_commerce/cart.php">
          Keranjang
          <?php if ($cart_count > 0): ?>
            <span class="badge bg-danger rounded-pill position-absolute" style="top:-6px;right:-6px;font-size:0.7rem"><?php echo $cart_count; ?></span>
          <?php endif; ?>
        </a>
        <a class="btn btn-outline-secondary" href="/e_commerce/admin/index.php">Admin</a>
      </div>
    </div>
  </div>
</nav>

<!-- Offcanvas Search for mobile -->
<div class="offcanvas offcanvas-top" tabindex="-1" id="offcanvasSearch" aria-labelledby="offcanvasSearchLabel">
  <div class="offcanvas-header">
    <h5 id="offcanvasSearchLabel">Cari produk</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-4">
    <form method="get" action="/e_commerce/index.php">
      <div class="input-group">
        <input id="offcanvas-search-input" type="search" name="q" class="form-control" placeholder="Cari produk atau kategori..." value="<?php echo e($_GET['q'] ?? ''); ?>">
        <button class="btn btn-success" type="submit">Cari</button>
      </div>
    </form>
  </div>
</div>
