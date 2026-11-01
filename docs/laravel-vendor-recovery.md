# Pemulihan ParseError Laravel vendor

Gejala: unexpected identifier "compileDropAllViews", expecting variable pada MySqlGrammar.php:697.

composer.lock saat pemeriksaan mengunci Laravel v12.64.0. Deklarasi upstream pada baris 697 adalah `public function compileDropAllViews($views)` dan valid untuk PHP yang didukung. File vendor produksi belum diperiksa; kerusakan atau perubahan file merupakan dugaan, bukan diagnosis terkonfirmasi. Folder vendor diabaikan Git, sehingga git pull saja tidak mengganti file tersebut.

## Pelaksanaan di server

Sediakan jendela pemeliharaan melalui panel hosting karena Composer akan mengganti framework di tempat. Cadangkan rilis aplikasi. Pastikan PHP CLI dan PHP-FPM situs sama-sama >= 8.2 serta Composer menggunakan PHP CLI tersebut. Gunakan akun pemilik aplikasi.

Setelah mengambil skrip dari branch/PR ini:

```bash
cd /www/wwwroot/mimuslimatnudemak.sch.id
php -v
composer --version
bash scripts/repair-laravel-vendor.sh
```

Skrip memeriksa kebutuhan platform, menyimpan salinan MySqlGrammar.php di direktori sementara privat, memasang ulang laravel/framework dengan versi yang sudah terpasang, kemudian memeriksa sintaks sebelum memuat Artisan. Composer reinstall memerlukan Composer 2 dan akses unduhan paket. Tidak ada migrasi, seeder, atau perubahan .env dalam skrip.

Periksa versi terpasang dengan `composer show laravel/framework` dan versi terkunci dengan `composer show --locked laravel/framework`. Jika berbeda, selaraskan dependensi dengan `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts` sebelum menjalankan skrip. Jangan menggunakan composer update untuk pemulihan insiden.

Jika reinstall gagal, hentikan deployment dan pulihkan rilis sebelumnya; jangan membuka kembali situs dengan vendor yang belum lengkap.

Setelah berhasil, muat ulang PHP-FPM untuk situs melalui panel hosting agar OPcache memakai file baru. Periksa halaman login dan log Laravel terbaru. Keberhasilan lokal/PR bukan bukti bahwa server produksi sudah pulih.

Jika ParseError tetap muncul, ambil keluaran `php -l vendor/laravel/framework/src/Illuminate/Database/Schema/Grammars/MySqlGrammar.php`, versi PHP-FPM situs, dan baris 680–705 file produksi. Jangan menghapus metode compileDropAllViews atau menyunting vendor secara manual sebagai perbaikan permanen.
