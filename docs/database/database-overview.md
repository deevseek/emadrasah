# Database Modul Penilaian dan Keuangan Operasional

Database utama aplikasi adalah MySQL melalui migration Laravel. Modul Penilaian memakai tabel akademik yang sudah ada (`academic_years`, `semesters`, `classrooms`, `students`, `subjects`, `teaching_assignments`) dan tabel khusus nilai (`assessment_schemes`, `assessment_periods`, `assessment_components`, `student_scores`, `subject_minimum_criteria`, `predicate_ranges`, `report_cards`, `report_card_subjects`). Modul Keuangan Operasional memakai tabel kas yang terintegrasi dengan modul keuangan siswa (`cash_accounts`, `chart_accounts`) serta tabel operasional (`finance_categories`, `operational_transactions`, `cash_transfers`, `budget_periods`, `budget_allocations`, `cash_closings`, `cash_reconciliations`, `transaction_attachments`, `transaction_approvals`, `finance_document_sequences`).

## Konvensi

- Nama tabel menggunakan snake case plural.
- Foreign key memakai suffix `_id` dan constraint Laravel `constrained()`.
- Nilai uang menggunakan decimal, bukan float.
- Status disimpan sebagai string berbasis enum PHP agar mudah diaudit dan kompatibel MySQL.
- Data master yang direferensikan dinonaktifkan dengan flag `is_active` atau dibatasi penghapusannya.

## Strategi integritas

- Nomor transaksi dan dokumen dibuat unik melalui sequence ter-lock di database.
- Operasi saldo, approval, pembatalan, transfer, rekonsiliasi, dan penutupan kas dilakukan dalam database transaction.
- Transfer antar-kas membuat pasangan mutasi keluar/masuk dan relasi `cash_transfers` sebagai dokumen induk.
- Nilai siswa disimpan per komponen dan dihitung ulang oleh service, bukan di Blade.
- Rapor menyimpan snapshot nilai, kehadiran, BTAQ, dan riwayat status.

## Audit trail

Perubahan penting dicatat melalui activity log yang sudah digunakan project. Catatan mencakup penyimpanan nilai, generate rapor, perubahan status rapor, pembuatan akun kas, pembuatan transaksi, submit, approve, reject, dan cancel.
