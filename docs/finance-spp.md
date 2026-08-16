# Keuangan dan SPP

Domain Finance memisahkan jenis biaya, invoice dan item, pembayaran, serta VA. Nominal menggunakan `decimal(15,2)` dan seluruh mutasi pembayaran harus melalui transaksi database. `MonthlySppService` menyediakan preview dan generate dengan unique constraint siswa/tahun/bulan sebagai pertahanan duplikasi. `InvoicePaymentService` mengunci invoice, membuat pembayaran idempoten berdasarkan referensi bank, lalu menghitung ulang saldo dari pembayaran backend berstatus sukses.

Alur: preview siswa aktif → konfirmasi → generate; pembayaran → verifikasi sumber → rekonsiliasi → hitung ulang invoice. Jangan mengubah nominal melalui browser atau callback tanpa inquiry provider.
