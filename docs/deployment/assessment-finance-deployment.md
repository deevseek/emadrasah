# Deployment Modul Assessment dan Finance

1. Backup database dengan `mysqldump --single-transaction`.
2. Aktifkan maintenance: `php artisan down`.
3. Pull branch PR dan install dependency: `composer install --no-dev --optimize-autoloader`.
4. Build asset: `npm install && npm run build`.
5. Jalankan migration: `php artisan migrate --force`.
6. Seed permission aman: `php artisan db:seed --class=RolePermissionSeeder --force`.
7. Pastikan storage link: `php artisan storage:link`.
8. Set permission folder `storage` dan `bootstrap/cache` writable oleh web user.
9. Bersihkan dan cache ulang: `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`.
10. Restart queue jika digunakan: `php artisan queue:restart`.
11. Nonaktifkan maintenance: `php artisan up`.
12. Post-deployment check: login admin, buka dashboard, input nilai draft, export leger, catat transaksi kas, export laporan.

## Rollback
- `php artisan down`
- Checkout commit stabil sebelumnya.
- `composer install --no-dev --optimize-autoloader`
- Jika migration perlu dibatalkan: `php artisan migrate:rollback --step=1 --force` atau restore backup.
- `php artisan optimize:clear && php artisan up`
