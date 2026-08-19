# Integrasi BRIAPI / SNAP BI

## Status dan arsitektur

Integrasi berada di dalam modular monolith Laravel. `BriSnapBiClient` adalah transport tunggal untuk token B2B dan request SNAP BI. Penerimaan tetap menggunakan `StudentInvoice`, `StudentVirtualAccount`, `BankTransaction`, `PaymentService`, dan `StudentPayment`; payroll tetap menggunakan `PersonnelPayroll`, `PayrollPaymentBatch`, dan `PayrollDisbursement`. Integrasi nonaktif secara default dan aplikasi manual tetap berjalan tanpa credential BRI.

Implementasi **belum boleh disebut production-ready** sebelum endpoint, response code, callback, key, dan credential resmi diuji di sandbox BRI milik madrasah.

## Credential dan key

Rahasia dapat diberikan melalui environment. Client secret dan nomor rekening yang disimpan melalui halaman pengaturan memakai encrypted cast Laravel. Private/public key diunggah ke disk private, bukan database atau public disk. Jangan commit `.env` maupun key.

```bash
openssl genrsa -out storage/app/private/bri-private.pem 2048
openssl rsa -in storage/app/private/bri-private.pem -pubout -out storage/app/private/bri-public.pem
chmod 600 storage/app/private/bri-private.pem
```

Atur `BRI_BASE_URL` dan semua `BRI_PATH_*` sesuai environment/dokumen produk yang diaktifkan. URL production maupun service code tidak di-hardcode karena nilainya bergantung onboarding. `X-EXTERNAL-ID` dibuat unik tanpa tanda hubung. Token di-cache berdasarkan `expiresIn` dikurangi safety window dan tidak disimpan ke database/log.

## Sandbox dan production

1. Set `BRI_ENABLED=true`, `BRI_ENV=sandbox`, URL sandbox, client/partner/channel ID, client secret, dan path key.
2. Isi rekening yang didaftarkan ke BRI; Balance Inquiry tidak menerima rekening dari request pengguna.
3. Isi path produk dan service code persis dari onboarding.
4. Jalankan **Test Koneksi**. Pengujian memperoleh token sungguhan dan, bila rekening tersedia, menjalankan Balance Inquiry.
5. Daftarkan callback HTTPS di BRI, lalu uji callback sah, signature salah, timestamp lama, replay, pembayaran kurang, dan lebih.
6. Pindah production hanya setelah hasil UAT disetujui; ganti URL/key/credential dengan nilai production dan kosongkan cache.

## Endpoint callback

- `POST /api/bri/snap-bi/briva/inquiry` — inquiry read-only.
- `POST /api/bri/snap-bi/briva/payment` — notifikasi pembayaran idempotent.
- `POST /api/bri/snap-bi/qris/payment` — notifikasi QRIS idempotent; response code sukses wajib diisi dari onboarding.

Endpoint tidak memakai CSRF karena berada di API group, tetapi wajib melewati signature, partner/channel, timestamp tolerance, replay marker, dan rate limit. Payload mentah hanya tersimpan terenkripsi pada `BankTransaction`; activity log hanya mencatat hash external ID dan nama endpoint.

## Flow

### BRIVA

VA dibuat deterministik menggunakan Partner Service ID onboarding dan customer number. Nomor VA adalah gabungan `partnerServiceId + customerNo`. Inquiry tidak mencatat pembayaran. Notifikasi membuat `BankTransaction`, lalu `BriReconciliationService` mengunci transaksi/tagihan dan memakai `PaymentService`; event `StudentPaymentCompleted` existing tetap menerbitkan kwitansi. Overpayment masuk `needs_review`, bukan diterima diam-diam.

### QRIS

`BriQrisService` mengirim nominal decimal IDR, merchant/terminal ID, reference unik, expiry, dan referensi invoice. Database hanya menyimpan string QR, bukan gambar base64. QR tidak dianggap berhasil sebelum notification/status inquiry. Callback QRIS harus didaftarkan setelah BRI mengonfirmasi kontrak payload dan response code produk pada sandbox.

### Bank Statement

`BriBankStatementService` selalu mengganti `accountNo` dengan rekening terdaftar. Mutasi diimpor idempotent dengan provider reference. Statement adalah jalur backup/audit, bukan primary matching ketika notifikasi BRIVA/QRIS tersedia.

### Payroll

Maker membuat batch, checker berbeda menyetujui, executor mengubah batch ke processing, lalu transfer diproses per disbursement. Rekening BRI memakai intrabank dan lainnya interbank. Timeout sesudah submit menghasilkan `pending_confirmation`; transfer tidak di-retry. Hanya kegagalan terkonfirmasi boleh diulang. Status pending/unknown diperiksa memakai reference yang sama.

## Queue dan scheduler

Jalankan worker dengan `php artisan queue:work --tries=1`. Interval default yang direkomendasikan: status setiap 10 menit, reconciliation 15 menit, statement 30 menit. Atur melalui `BRI_*_SCHEDULE`; gunakan `withoutOverlapping` dan jangan menjalankan transfer sebagai job dengan retry otomatis.

## Checklist onboarding

- [ ] Client ID
- [ ] Client Secret
- [ ] Partner ID
- [ ] Channel ID
- [ ] Private/Public Key
- [ ] Registered Account
- [ ] BRIVA Partner Service ID
- [ ] BRIVA callback registered
- [ ] QRIS Merchant ID
- [ ] QRIS Terminal ID
- [ ] QRIS callback registered
- [ ] Payroll source account
- [ ] Intrabank service code
- [ ] Interbank service code
- [ ] Status inquiry service code
- [ ] Base URL dan path setiap produk untuk sandbox/production
- [ ] Response code sukses tiap produk dikonfirmasi melalui UAT

## Troubleshooting

- `Signature tidak valid`: pastikan timestamp berformat ISO-8601, body tidak berubah, path mencakup slash awal, dan secret sesuai environment.
- `Timestamp kedaluwarsa`: sinkronkan NTP server dan periksa timezone.
- Timeout transfer: jangan submit ulang; jalankan status inquiry dengan partner reference lama.
- Mutasi tidak match: periksa provider/partner reference dan VA/invoice. Jangan match hanya berdasarkan nominal.
- Token gagal: validasi pasangan key, Client ID, URL environment, dan jam server. Token/secret jangan ditempel ke tiket atau log.
