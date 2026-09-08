# Pelayanan Email

## Kotak masuk

Kotak masuk menerima pesan dari penyedia email melalui endpoint `POST /api/webhooks/email/incoming`. Atur `INBOUND_EMAIL_TOKEN` dengan nilai acak yang kuat di lingkungan deployment, lalu konfigurasikan penyedia agar mengirim nilai tersebut melalui header `X-Inbound-Email-Token`. Token tidak boleh disimpan di repositori.

Payload menggunakan `multipart/form-data` dengan field wajib `message_id`, `from_address`, `to_addresses[]`, dan `received_at`. Field opsionalnya adalah `from_name`, `cc_addresses[]`, `subject`, `body`, serta maksimal sepuluh `attachments[]`. `message_id` harus stabil dan unik agar webhook yang dikirim ulang tidak menggandakan email. Lampiran disimpan sebagai file pada disk lokal privat, bukan sebagai base64 di database.

Membuka detail email akan menandainya sebagai sudah dibaca. Petugas dapat menyaring email yang belum dibaca, mencari berdasarkan pengirim atau subjek, mengunduh lampiran, dan membuka formulir balasan yang sudah berisi alamat pengirim serta subjek balasan.

## Email keluar

Pengiriman email yang dibuat secara manual dari menu **Pelayanan Email** diproses langsung ketika pengguna menekan tombol **Kirim Email**. Fitur ini tidak memerlukan perintah `queue:work`; halaman hasil akan menampilkan status **Terkirim** atau **Gagal** setelah layanan SMTP merespons.

Untuk membatasi akses petugas, tetapkan role sistem **Petugas Email** pada akun yang bertanggung jawab. Role ini hanya memperoleh akses dashboard serta hak untuk melihat, membuat, mengirim, dan mengirim ulang email pelayanan. Penetapan role tetap dilakukan oleh pengguna yang memiliki hak akses pengelolaan role pengguna.

Worker antrean tetap wajib dijalankan secara permanen untuk email otomatis dari modul lain, misalnya notifikasi portal dan tanda terima pembayaran. Jalankan worker di bawah process manager seperti Supervisor atau systemd:

```bash
php artisan queue:work --tries=1 --timeout=120
```

Setelah deployment, muat ulang worker agar menggunakan kode dan konfigurasi terbaru:

```bash
php artisan queue:restart
```

Jika pengiriman email manual gagal, periksa log aplikasi dan konfigurasi SMTP. Untuk antrean email otomatis, periksa proses worker, koneksi antrean pada `.env`, serta tabel `jobs`/`failed_jobs`. Jangan menaruh kredensial SMTP di dokumentasi atau repositori.
