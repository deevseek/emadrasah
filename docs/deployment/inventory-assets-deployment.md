# Deployment Modul Inventaris dan Aset

1. Backup database dan storage: `mysqldump` serta arsip `storage/app`.
2. Pull branch rilis, jalankan `composer install --no-dev --optimize-autoloader` dan `npm ci && npm run build`.
3. Jalankan `php artisan migrate --force` untuk tabel inventaris baru.
4. Jalankan seeder permission/master bila dibutuhkan: `php artisan db:seed --class=InventoryPermissionSeeder` dan `php artisan db:seed --class=InventoryMasterSeeder`.
5. Pastikan `storage` dan `bootstrap/cache` writable, lalu `php artisan optimize:clear`, `config:cache`, `route:cache`, `view:cache`.
6. Restart queue/worker dan scheduler supervisor jika digunakan.
7. Post-deployment: login admin, buka dashboard inventaris, buat barang uji, export laporan.
8. Rollback: restore backup database atau rollback migration inventaris jika belum ada data produksi; jika sudah ada data, jangan drop tabel tanpa backup dan gunakan reversal transaksi untuk koreksi saldo.
