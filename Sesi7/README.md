# Sesi7 - Form Input Produk (PHP)

File di folder ini:
- `index.php` - Form input produk (UI). File ini menyertakan `backend.php` sehingga variabel hasil validasi ditampilkan di halaman form.
- `backend.php` - Logika tugas dasar PHP: deklarasi variabel, operator, if-else, memproses form POST (nama, harga, deskripsi) dan validasi sederhana.

Cara menjalankan (XAMPP, Windows):
1. Pastikan XAMPP terinstall dan Apache berjalan (Control Panel -> Start Apache).
2. Buka browser dan kunjungi:
   http://localhost/Bootcamp-EduWork/Sesi7/index.php
3. Isi form dan klik "Simpan Produk".
   - Form akan POST ke `backend.php`.
   - Jika ada error (field kosong atau harga bukan angka/<=0), pesan error muncul.
   - Jika valid, `backend.php` menampilkan halaman hasil (simulasi penyimpanan) dan juga `index.php` akan menampilkan pesan sukses setelah include.

Catatan teknis:
- `backend.php` menyiapkan variabel: `$nama`, `$harga`, `$deskripsi`, `$error`, `$sukses`.
- `index.php` include `backend.php` sehingga form dapat menampilkan ulang nilai input saat terjadi error.
- Saat `backend.php` diakses langsung (action form), ia akan merender hasil HTML singkat lalu exit.

