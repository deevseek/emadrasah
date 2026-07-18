# Deployment Modul Inventaris dan Aset

1. Backup database dan storage: `mysqldump` serta arsip `storage/app`.
2. Pull branch rilis, jalankan `composer install --no-dev --optimize-autoloader` dan `npm ci && npm run build`.
3. Jalankan `php artisan migrate --force` untuk tabel inventaris baru.
4. Jalankan seeder permission/master bila dibutuhkan: `php artisan db:seed --class=InventoryPermissionSeeder` dan `php artisan db:seed --class=InventoryMasterSeeder`.
5. Pastikan `storage` dan `bootstrap/cache` writable, lalu `php artisan optimize:clear`, `config:cache`, `route:cache`, `view:cache`.
6. Restart queue/worker dan scheduler supervisor jika digunakan.
7. Post-deployment: login admin, buka dashboard inventaris, buat barang uji, export laporan.
8. Rollback: restore backup database atau rollback migration inventaris jika belum ada data produksi; jika sudah ada data, jangan drop tabel tanpa backup dan gunakan reversal transaksi untuk koreksi saldo.

## Deployment Navigasi Inventaris di Production

Setelah PR navigasi Inventaris di-merge ke `main`, gunakan urutan aman berikut pada server production:

```bash
cd /www/wwwroot/emadrasah
git fetch origin
git checkout main
git pull origin main
php artisan down
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=InventoryPermissionSeeder --force
php artisan db:seed --class=InventoryMasterSeeder --force
php artisan permission:cache-reset || php artisan tinker
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

Jika `permission:cache-reset` tidak tersedia, jalankan di Tinker: `app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();`. Verifikasi `php artisan route:list | grep -Ei "inventory|inventaris"` dan pastikan user admin dapat `inventory.view` tanpa hardcode email di source code.
