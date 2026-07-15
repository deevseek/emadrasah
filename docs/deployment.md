# Deployment Production

## Ubuntu, Nginx, PHP-FPM, MySQL

1. Siapkan PHP 8.2+, ekstensi umum Laravel, Composer, Node.js 20+, MySQL 8/MariaDB kompatibel, Nginx, Supervisor.
2. Buat database MySQL dengan charset `utf8mb4` dan collation `utf8mb4_unicode_ci`.
3. Clone repository ke `/var/www/emadrasah` dan set permission storage/cache untuk user web server.
4. Gunakan `.env` production dengan `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`, database valid, queue database, session database.

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan queue:restart
```

## Scheduler

```cron
* * * * * cd /path/to/emadrasah && php artisan schedule:run >> /dev/null 2>&1
```

## Supervisor Queue

Program queue menjalankan `php artisan queue:work database --sleep=3 --tries=3 --timeout=120` dengan autorestart. Jangan jalankan restore backup dari tombol publik; prosedur restore dilakukan manual oleh administrator server setelah verifikasi file backup.
