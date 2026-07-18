# Modul 12 — Keuangan Operasional/Bendahara

## Tujuan
Mengelola kas operasional madrasah tanpa menduplikasi modul pembayaran siswa atau payroll.

## Fitur
- Master akun kas/rekening, kategori transaksi, periode anggaran, dan realisasi.
- Pemasukan, pengeluaran, submit, approval, reject, cancel/reversal berbasis audit.
- Transfer antar-kas atomik dengan mutasi keluar dan masuk.
- Buku kas, dashboard saldo, laporan, CSV export sesuai filter aktif.
- Rekonsiliasi dan penutupan kas dengan catatan selisih.

## Role dan permission
Permission alias utama: `finance.view`, `finance.create`, `finance.update`, `finance.delete`, `finance.verify`, `finance.approve`, `finance.reverse`, `finance.transfer`, `finance.reconcile`, `finance.close-period`, `finance.export`, `finance.report`. Permission route lama tetap digunakan, seperti `operational-incomes.create`, `cash-transfers.create`, `cash-books.export`.

## Alur kerja transaksi
1. Bendahara membuat draft pemasukan/pengeluaran.
2. Draft diajukan.
3. Kepala madrasah/verifikator menyetujui atau menolak.
4. Transaksi approved diposting dan saldo kas berubah.
5. Transaksi posted tidak dihapus; pembatalan wajib menyertakan alasan dan membalik saldo.

## Business rules
- Nominal harus lebih dari nol.
- Saldo tidak boleh negatif kecuali akun mengizinkan `allow_negative_balance`.
- Pengaju tidak dapat menyetujui transaksi sendiri.
- Transfer sumber dan tujuan harus berbeda serta berjalan dalam transaction.
- Realisasi anggaran bertambah saat pengeluaran posted.

## Route utama
- `/operational-finance` dashboard.
- `/operational-finance/cash-accounts` akun kas.
- `/operational-finance/categories` kategori.
- `/operational-finance/incomes` pemasukan.
- `/operational-finance/expenses` pengeluaran.
- `/operational-finance/transfers` transfer kas.
- `/operational-finance/cash-book` buku kas.
- `/operational-finance/reports` laporan dan export.

## Service utama
- `App\Services\OperationalFinance\OperationalFinanceService`
- `App\Services\OperationalFinance\CashBalanceService`
- Action class operational finance yang sudah tersedia.

## Export
CSV laporan berisi nomor, tanggal, jenis, akun, kategori, uraian, pemasukan, pengeluaran, status, dan baris total. Export mengikuti filter tanggal, akun, kategori, status, jenis, dan pencarian.

## Known limitations
Lampiran transaksi sudah memiliki struktur tabel, tetapi acceptance visual preview lampiran perlu diverifikasi di browser/staging.

## Troubleshooting
- Jika nomor dokumen ganda, cek lock database dan tabel `finance_document_sequences`.
- Jika saldo tidak sesuai, cocokkan transaksi `posted` dengan `cash_reconciliations` terbaru.
