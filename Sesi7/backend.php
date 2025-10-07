<?php
// backend.php
// Tugas Dasar PHP: file ini menunjukkan deklarasi variabel, operator, dan if-else
// Juga memproses data form (nama, harga, deskripsi) dan melakukan validasi sederhana

// -------------------------
// 1) Deklarasi variabel
// -------------------------
$nama = '';
$harga = '';
$deskripsi = '';
$error = '';
$sukses = false;
$note = '';

// Contoh variabel lain dan operator (tugas dasar)
$x = 7;            // deklarasi integer
$y = 3;            // deklarasi integer
$sum = $x + $y;    // operator penjumlahan (hasil 10)

// -------------------------
// 2) Proses form POST
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Ambil data dengan aman
	$nama = trim($_POST['nama'] ?? '');
	$harga = trim($_POST['harga'] ?? '');
	$deskripsi = trim($_POST['deskripsi'] ?? '');

	// Validasi sederhana: semua field wajib diisi
	if ($nama === '' || $harga === '' || $deskripsi === '') {
		$error = 'Semua field wajib diisi.'; // if-else sederhana
	} else {
		// Validasi harga: harus numeric
		if (!is_numeric($harga)) {
			$error = 'Harga harus berupa angka.';
		} else {
			// Konversi harga ke angka
			$harga_num = floatval($harga);
			if ($harga_num <= 0) {
				$error = 'Harga harus lebih besar dari nol.';
			} else {
				// Semua valid -> simulasi simpan
				$sukses = true;
				// Contoh if-else tambahan: cek apakah harga lebih besar dari 10000
				if ($harga_num > 10000) {
					$note = 'Produk ini termasuk mahal (demo if-else).';
				} else {
					$note = 'Produk dengan harga terjangkau (demo if-else).';
				}
			}
		}
	}
}

// Jika file ini diakses langsung (action form mengarah ke backend.php), render hasil sederhana
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
	?>
	<!doctype html>
	<html lang="id">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,initial-scale=1">
		<title>Hasil Proses Produk</title>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
		<style>
			.result-card { background: #e9f7ee; border: 1px solid #d1ecd7; border-radius: .6rem; }
		</style>
	</head>
	<body class="bg-light">
	<div class="container py-5">
		<?php if ($error !== ''): ?>
			<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
			<a href="index.php" class="btn btn-secondary">Kembali ke form</a>
		<?php elseif ($sukses): ?>
			<div class="card result-card p-3 mb-3">
				<h5 class="mb-3">Produk berhasil disimpan (simulasi)</h5>
				<p class="mb-1"><strong>Nama:</strong> <?= htmlspecialchars($nama) ?></p>
				<p class="mb-1"><strong>Harga:</strong> <?= htmlspecialchars(number_format((float)$harga,0,',','.')) ?></p>
				<p class="mb-1"><strong>Deskripsi:</strong><br><?= nl2br(htmlspecialchars($deskripsi)) ?></p>
				<?php if ($note !== ''): ?><p class="small text-muted mt-2"><?= htmlspecialchars($note) ?></p><?php endif; ?>
			</div>
			<a href="index.php" class="btn btn-primary">Tambah produk lagi</a>
		<?php else: ?>
			<div class="alert alert-info">Silakan isi form di <a href="index.php">index.php</a>.</div>
		<?php endif; ?>
	</div>
	</body>
	</html>
	<?php
	exit;
}

// Jika di-include oleh index.php, file hanya menyiapkan variabel untuk digunakan
?>
