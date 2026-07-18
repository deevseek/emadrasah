# Migration dan Rollback Modul Penilaian/Keuangan

## Urutan migration terkait

1. `2026_07_16_040000_create_btaq_assessment_report_card_tables.php`
2. `2026_07_18_100000_add_module_10_assessment_report_tables.php`
3. `2026_07_16_120000_create_finance_student_payroll_tables.php`
4. `2026_07_18_120000_create_operational_finance_tables.php`
5. `2026_07_18_121000_harden_cash_accounts_for_operational_finance.php`

## Risiko

- Penambahan unique index dapat gagal jika ada data duplikat.
- Foreign key dapat gagal jika ada orphan record dari data lama.
- Saldo kas harus dicocokkan dengan transaksi posted sebelum go-live.

## Deploy

```bash
php artisan down
mysqldump --single-transaction --routines --triggers nama_db > backup.sql
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

## Rollback

Rollback normal:

```bash
php artisan down
php artisan migrate:rollback --step=1 --force
php artisan optimize:clear
php artisan up
```

Jika migration gagal setelah sebagian perubahan, restore backup MySQL, deploy ulang kode stabil, lalu jalankan `php artisan migrate:status` untuk audit.
