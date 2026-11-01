#!/usr/bin/env bash
# Jalankan dari checkout aplikasi, dengan PHP CLI yang sama dengan PHP-FPM.
set -euo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.."

command -v php >/dev/null || { echo "PHP CLI tidak ditemukan." >&2; exit 1; }
command -v composer >/dev/null || { echo "Composer tidak ditemukan." >&2; exit 1; }
php -r 'if (PHP_VERSION_ID < 80200) { fwrite(STDERR, "Diperlukan PHP >= 8.2.\n"); exit(1); }'
test -f composer.lock || { echo "composer.lock tidak ditemukan." >&2; exit 1; }
test -f vendor/composer/installed.json || { echo "Jalankan composer install --no-dev --prefer-dist terlebih dahulu." >&2; exit 1; }

# Periksa platform tanpa memuat Laravel yang mungkin rusak.
composer check-platform-reqs --no-dev
grammar=vendor/laravel/framework/src/Illuminate/Database/Schema/Grammars/MySqlGrammar.php
backup_dir="$(mktemp -d /tmp/emadrasah-vendor-recovery.XXXXXXXX)"
if test -f "$grammar"; then
    cp -- "$grammar" "$backup_dir/MySqlGrammar.php.before"
fi

# install saja tidak mengganti paket yang sudah dianggap terpasang.
# Jangan jalankan hook Artisan sebelum framework lolos pemeriksaan sintaks.
composer reinstall laravel/framework --prefer-dist --no-interaction --no-scripts
php -l "$grammar"
composer dump-autoload --no-dev --optimize --no-interaction --no-scripts
php artisan package:discover --ansi
php artisan config:clear
php artisan view:clear
php artisan --version
echo "Framework terpasang ulang. Cadangan file: $backup_dir"
echo "Muat ulang PHP-FPM melalui panel hosting, lalu periksa situs dan log terbaru."
