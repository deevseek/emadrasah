# Deployment API absensi RFID

Endpoint absensi hanya menerima `POST /api/rfid/attendance`. Laravel tidak melakukan pengalihan skema atau host untuk endpoint ini. Jika perangkat menerima pengalihan sebelum respons Laravel, periksa setiap lapisan Cloudflare, load balancer, dan Nginx.

## Reverse proxy tepercaya

Isi `TRUSTED_PROXIES` dengan IP atau CIDR **proxy internal yang langsung terhubung ke PHP/Laravel**, dipisahkan koma. Biarkan kosong bila PHP dapat diakses langsung. Jangan memakai `*` atau mempercayai seluruh internet. Setelah mengubah env, jalankan `php artisan optimize:clear`.

Contoh tanpa mengikat konfigurasi ke alamat production tertentu:

```dotenv
APP_URL=https://nama-domain.example
TRUSTED_PROXIES=10.0.0.10,10.0.1.0/24
RFID_ATTENDANCE_DIAGNOSTICS=false
```

`RFID_ATTENDANCE_DIAGNOSTICS=true` mencatat method, path, skema, host, status HTTPS, header proxy, user-agent, dan IP. Log tidak memuat `X-Device-Token` maupun `card_token`; aktifkan hanya sementara saat investigasi.

## Pemeriksaan production

Jalankan tanpa `-L` agar redirect pertama terlihat:

```bash
curl -i -X POST 'https://mimuslimatnudemak.sch.id/api/rfid/attendance' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Device-Id: ...' \
  -H 'X-Device-Token: ...' \
  --data '{"card_token":"7142C511E9A163679A43E2259A723517","uid":"E797BC64"}'

curl -sS -o /dev/null -D - --trace-ascii - \
  -X POST 'https://mimuslimatnudemak.sch.id/api/rfid/attendance' \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  --data '{}'
```

Bandingkan akses domain publik dengan akses langsung ke origin (gunakan IP/host internal yang benar):

```bash
curl -ik --resolve mimuslimatnudemak.sch.id:443:IP_ORIGIN \
  -X POST 'https://mimuslimatnudemak.sch.id/api/rfid/attendance' \
  -H 'Accept: application/json' -H 'Content-Type: application/json' --data '{}'
```

Respons tanpa kredensial yang benar harus `401 DEVICE_UNAUTHORIZED`, tanpa status 3xx. Periksa log akses pada setiap hop beserta `$request_method`, `$status`, dan `$sent_http_location`.

## Nginx/PHP-FPM

Pastikan blok aplikasi mempertahankan method asli dan tidak memiliki `return 301`, `rewrite ... permanent`, `error_page` ke URI lain, atau canonical redirect lain untuk `/api/`. Jika redirect HTTPS/host memang wajib bagi API, gunakan `307` atau `308`, bukan `301`/`302`.

Konfigurasi front controller yang lazim adalah:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param REQUEST_METHOD $request_method;
    fastcgi_pass unix:/run/php/php-fpm.sock;
}
```

Untuk proxy HTTP, hindari `proxy_pass` yang mengubah URI dan teruskan informasi protokol secara eksplisit:

```nginx
proxy_set_header Host $host;
proxy_set_header X-Forwarded-Host $host;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
```

Sesudah deployment, bersihkan cache lalu verifikasi route:

```bash
php artisan optimize:clear
php artisan route:list --path=api/rfid -v
php artisan route:cache
php artisan route:list --path=api/rfid -v
```
