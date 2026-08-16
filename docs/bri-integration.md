# Integrasi Bank BRI

## Status dan arsitektur

Integrasi dinonaktifkan secara default. Contract `BankPaymentGateway` dan `BankTransferGateway` mencegah logika bank masuk controller. `FakeBriGateway` hanya digunakan dalam testing. Adapter produksi tidak mengarang endpoint: sampai produk BRIVA/transfer, spesifikasi signature, dan credential institusi disetujui BRI, `DisabledBriGateway` menolak transaksi.

## Konfigurasi

Isi `BRI_ENABLED`, `BRI_ENV`, `BRI_BASE_URL`, `BRI_CLIENT_ID`, `BRI_CLIENT_SECRET`, `BRI_PARTNER_ID`, `BRI_CHANNEL_ID`, path kunci, kode institusi BRIVA, mode VA, dan rekening sumber melalui secret manager/env deployment. Jangan simpan token atau kunci di database/log. Mode VA mendukung `per_student` dan `per_invoice`.

## Production checklist

Konfirmasi produk dan endpoint resmi, pasang kunci di luar web root, validasi signature/timestamp dan replay, aktifkan rate limit callback, uji inquiry, timeout dan idempotency di sandbox, lalu lakukan maker-checker. Timeout transfer menjadi reconciliation-required dan tidak boleh dikirim ulang sebelum inquiry. Rekening personnel dan snapshot tujuan memakai encrypted cast serta harus ditampilkan termasking.

## Troubleshooting

Pesan “integrasi dinonaktifkan” berarti onboarding/config belum lengkap. Jangan mengaktifkan produksi sebagai workaround. Callback ganda harus menghasilkan satu `bank_transactions` dan satu pembayaran karena referensi unik.
