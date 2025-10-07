<?php
// index.php: Form input produk
// Meng-include backend.php agar koneksi proses & validasi berada di satu tempat
require_once __DIR__ . '/backend.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Form Input Produk</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
	<style>
		#formCard { border-radius: 1rem; }
	</style>
</head>
<body class="bg-light">
<div class="container py-5">
	<div class="row justify-content-center">
		<div class="col-md-6">
			<div class="card shadow-sm" id="formCard">
				<div class="card-header bg-primary text-white fw-bold">Form Input Produk Baru</div>
				<div class="card-body">
					<?php if ($error !== ''): ?>
						<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
					<?php endif; ?>

					<?php if ($sukses): ?>
						<div class="alert alert-success">Produk berhasil disimpan.</div>
					<?php endif; ?>

					<form method="POST" action="backend.php">
						<div class="mb-3">
							<label for="nama" class="form-label">Nama Produk</label>
							<input type="text" class="form-control" id="nama" name="nama" required value="<?= htmlspecialchars($nama) ?>">
						</div>
						<div class="mb-3">
							<label for="harga" class="form-label">Harga</label>
							<input type="number" class="form-control" id="harga" name="harga" required value="<?= htmlspecialchars($harga) ?>">
						</div>
						<div class="mb-3">
							<label for="deskripsi" class="form-label">Deskripsi</label>
							<textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" required><?= htmlspecialchars($deskripsi) ?></textarea>
						</div>
						<button type="submit" class="btn btn-primary w-100" id="btnSimpan">Simpan Produk</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	// Interaksi kecil untuk visual
	const card = document.getElementById('formCard');
	if (card) {
		card.style.boxShadow = '0 6px 20px rgba(0,0,0,0.06)';
	}
</script>
</body>
</html>
