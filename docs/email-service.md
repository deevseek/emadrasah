# Pelayanan Email

Pengiriman email yang dibuat secara manual dari menu **Pelayanan Email** diproses langsung ketika pengguna menekan tombol **Kirim Email**. Fitur ini tidak memerlukan perintah `queue:work`; halaman hasil akan menampilkan status **Terkirim** atau **Gagal** setelah layanan SMTP merespons.

Worker antrean tetap wajib dijalankan secara permanen untuk email otomatis dari modul lain, misalnya notifikasi portal dan tanda terima pembayaran. Jalankan worker di bawah process manager seperti Supervisor atau systemd:

```bash
php artisan queue:work --tries=1 --timeout=120
```

Setelah deployment, muat ulang worker agar menggunakan kode dan konfigurasi terbaru:

```bash
php artisan queue:restart
```

Jika pengiriman email manual gagal, periksa log aplikasi dan konfigurasi SMTP. Untuk antrean email otomatis, periksa proses worker, koneksi antrean pada `.env`, serta tabel `jobs`/`failed_jobs`. Jangan menaruh kredensial SMTP di dokumentasi atau repositori.
