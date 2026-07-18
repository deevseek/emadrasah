# Test Report Inventaris dan Aset

Tanggal: 18 Juli 2026. Environment: container `/workspace/emadrasah`. Pemeriksaan awal `git fetch origin` gagal karena remote `origin` tidak tersedia. `composer install` terkendala proxy GitHub 403 sehingga vendor tidak lengkap pada awal sesi; quality gate akhir dicatat faktual di laporan akhir.

Automated test ditambahkan: CRUD barang, validasi saldo ledger (saldo awal, mutasi, perubahan kondisi), saldo tidak cukup, export CSV, dan perhitungan selisih stock opname.
