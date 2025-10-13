<?php
require_once __DIR__ . '/config.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Jika user menekan batal pada form sebelum menyimpan
  if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
  // cukup kosongkan keranjang dan redirect kembali
    unset($_SESSION['cart']);
    header('Location: index.php');
    exit;
  }

  $nama = trim($_POST['nama'] ?? '');
  $email = trim($_POST['email'] ?? '');
    if ($nama === '' || $email === '') {
        $errors[] = 'Nama dan email wajib diisi.';
    }
    if (empty($errors)) {
  // Periksa apakah user sudah ada
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        if ($user) {
            $user_id = $user['id'];
        } else {
            // buat user sederhana dengan password acak
            $pwd = password_hash(bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $nama, $email, $pwd);
            $stmt->execute();
            $user_id = $stmt->insert_id;
        }

  // Masukkan entri order untuk setiap item di keranjang
        $mysqli->begin_transaction();
    try {
      $order_stmt = $mysqli->prepare("INSERT INTO orders (user_id, product_id, quantity, total, created_at) VALUES (?, ?, ?, ?, NOW())");
      $saved_order_ids = [];
      foreach ($cart as $it) {
        $product_id = $it['id'];
        $quantity = $it['quantity'];
        $total_price = $it['harga'] * $quantity;
        $order_stmt->bind_param('iiid', $user_id, $product_id, $quantity, $total_price);
        $order_stmt->execute();
        $saved_order_ids[] = $order_stmt->insert_id;
      }
      $mysqli->commit();
      $success = 'Pesanan berhasil disimpan. Terima kasih!';
  // simpan ID order yang baru dibuat di session agar user bisa membatalkan jika perlu
      $_SESSION['last_orders'] = $saved_order_ids;
  // kosongkan keranjang
      unset($_SESSION['cart']);
    } catch (Exception $e) {
      $mysqli->rollback();
      $errors[] = 'Gagal menyimpan pesanan: ' . $e->getMessage();
    }
    }
}

// Tangani pembatalan pesanan terakhir (GET atau POST)
if (isset($_GET['cancel_last']) && $_GET['cancel_last'] == '1') {
  $last = $_SESSION['last_orders'] ?? [];
  if (!empty($last)) {
    $placeholders = implode(',', array_fill(0, count($last), '?'));
  // buat string types dan params secara dinamis
    $types = str_repeat('i', count($last));
    $sql = "DELETE FROM orders WHERE order_id IN ($placeholders)";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
  // bind parameter secara dinamis
      $refs = [];
      foreach ($last as $k => $v) $refs[$k] = &$last[$k];
      array_unshift($refs, $types);
      call_user_func_array([$stmt, 'bind_param'], $refs);
      $stmt->execute();
      unset($_SESSION['last_orders']);
      $success = 'Pesanan terakhir berhasil dibatalkan.';
    } else {
      $errors[] = 'Gagal membatalkan pesanan: tidak dapat menyiapkan query.';
    }
  } else {
    $errors[] = 'Tidak ada pesanan terakhir yang dapat dibatalkan.';
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout</title>
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
      <a href="cart.php">&larr; Kembali ke Keranjang</a>
      <h1 class="mt-3">Checkout</h1>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success"><?php echo e($success); ?></div>
        <a href="index.php" class="btn btn-primary">Kembali ke Beranda</a>
      <?php else: ?>
        <div class="card p-4">
          <form method="post">
            <div class="mb-3">
              <label class="form-label">Nama</label>
              <input type="text" name="nama" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <strong>Total yang harus dibayar: <span class="text-primary">Rp <?php echo number_format(array_sum(array_map(function($i){return $i['harga']*$i['quantity'];}, $cart)), 0, ',', '.'); ?></span></strong>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-success">Bayar dan Simpan Pesanan</button>
              <button class="btn btn-outline-secondary" type="submit" name="action" value="cancel">Batal</button>
            </div>
          </form>
        </div>

        <!-- Prominent cancel form so user can cancel without filling the inputs -->
        <div class="mt-3">
          <form method="post" onsubmit="return confirm('Anda yakin ingin membatalkan pesanan dan mengosongkan keranjang?');">
            <input type="hidden" name="action" value="cancel">
            <button type="submit" class="btn btn-danger">Batalkan Pesanan</button>
          </form>
        </div>
      <?php endif; ?>
      
      <?php if ($success && !empty($_SESSION['last_orders'])): ?>
        <div class="mt-3">
          <a href="?cancel_last=1" class="btn btn-warning" id="cancel-last">Batalkan Pesanan Terakhir</a>
        </div>
      <?php endif; ?>
      <?php require __DIR__ . '/partials/footer.php'; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        var btn = document.getElementById('cancel-last');
        if (btn) {
          btn.addEventListener('click', function(e){
            if (!confirm('Anda yakin ingin membatalkan pesanan terakhir? Aksi ini tidak dapat dibatalkan.')) {
              e.preventDefault();
            }
          });
        }
      });
    </script>
  </body>
</html>
