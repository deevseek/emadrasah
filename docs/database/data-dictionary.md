# Data Dictionary Modul 10 dan Modul 12

## Modul 10 — Penilaian, Leger, Rapor

| Tabel | Fungsi | Kolom utama | Constraint/Index | Relasi | Business rule |
|---|---|---|---|---|---|
| `assessment_schemes` | Konfigurasi skema nilai | `academic_year_id`, `semester_id`, `classroom_id`, `subject_id`, `calculation_method`, `rounding_precision`, `is_active` | kombinasi konteks aktif harus tidak duplikatif di service/UI | tahun ajaran, semester, kelas, mapel | Bobot komponen aktif wajib sesuai aturan skema. |
| `assessment_periods` | Periode input/verifikasi/publish | `academic_year_id`, `semester_id`, `name`, `starts_on`, `ends_on`, `status`, `locked_at` | index tahun/semester/status | tahun ajaran, semester | Periode terkunci tidak menerima perubahan nilai biasa. |
| `assessment_components` | Komponen nilai | `classroom_id`, `subject_id`, `semester_id`, `type`, `weight`, `maximum_score`, `status` | index kelas/mapel/semester/status | kelas, mapel, semester, skor siswa | Komponen published/locked tidak boleh diedit sembarangan. |
| `student_scores` | Nilai per siswa-komponen | `assessment_component_id`, `student_id`, `score`, `remedial_score`, `final_score`, `predicate`, `entered_by` | unique komponen+siswa | komponen, siswa, user | Nilai divalidasi 0 sampai maksimum komponen. |
| `subject_minimum_criteria` | KKM per konteks | `academic_year_id`, `semester_id`, `classroom_id`, `subject_id`, `minimum_score` | unique konteks | tahun, semester, kelas, mapel | KKM harus berada di rentang nilai valid. |
| `predicate_ranges` | Rentang predikat | `code`, `minimum_score`, `maximum_score`, `description`, `sequence` | index aktif/sequence | digunakan service nilai | Rentang tidak boleh tumpang tindih secara bisnis. |
| `report_cards` | Dokumen rapor siswa | `student_id`, `student_enrollment_id`, `classroom_id`, `semester_id`, `status`, `document_number` | unique dokumen per siswa/semester | siswa, enrollment, kelas, semester | Status mengalir draft → submitted → approved/published → locked/reopened. |
| `report_card_subjects` | Baris nilai rapor | `report_card_id`, `subject_id`, `final_score`, `predicate`, `minimum_passing_grade`, `achievement_description` | index report/subject | rapor, mapel | Berisi snapshot nilai saat generate. |
| `report_card_status_histories` | Riwayat status rapor | `report_card_id`, `from_status`, `to_status`, `reason`, `changed_by` | index rapor | rapor, user | Alasan wajib untuk reopen/return. |
| `report_card_btaqs` | Snapshot capaian BTAQ | `report_card_id`, `final_score`, `predicate`, `achievement_description`, `needs_guidance` | one-to-one rapor | rapor | Diisi bila data BTAQ tersedia. |

## Modul 12 — Keuangan Operasional/Bendahara

| Tabel | Fungsi | Kolom utama | Constraint/Index | Relasi | Business rule |
|---|---|---|---|---|---|
| `cash_accounts` | Akun kas/rekening | `code`, `name`, `account_type`, `opening_balance`, `current_balance`, `is_active`, `allow_negative_balance` | unique kode bila diisi | chart account, transaksi | Saldo tidak boleh negatif kecuali diizinkan. |
| `finance_categories` | Kategori pemasukan/pengeluaran | `code`, `name`, `transaction_type`, `is_budgetable`, `requires_approval` | unique code, index type/active | parent category, transaksi, anggaran | Kategori aktif dipakai form transaksi. |
| `operational_transactions` | Mutasi kas operasional | `transaction_number`, `transaction_date`, `transaction_type`, `cash_account_id`, `amount`, `status`, `posted_at` | unique nomor, unique sumber integrasi | akun kas, kategori, user | Posted tidak dihapus; koreksi via cancel/reversal. |
| `cash_transfers` | Dokumen transfer kas | `transfer_number`, `source_cash_account_id`, `destination_cash_account_id`, `amount`, `out_transaction_id`, `in_transaction_id` | unique nomor | akun sumber/tujuan, transaksi keluar/masuk | Transfer atomik dan akun sumber ≠ tujuan. |
| `budget_periods` | Periode anggaran | `fiscal_year`, `start_date`, `end_date`, `total_budget`, `status` | index tahun/status | alokasi anggaran | Periode ditutup tidak menerima transaksi biasa. |
| `budget_allocations` | Alokasi dan realisasi | `budget_period_id`, `finance_category_id`, `allocated_amount`, `revised_amount`, `realized_amount` | unique periode+kategori | periode, kategori | Realisasi bertambah saat expense posted. |
| `cash_closings` | Tutup kas/periode | `cash_account_id`, `closing_date`, `expected_balance`, `actual_balance`, `difference`, `status` | index akun/tanggal | akun, user | Selisih wajib diberi catatan. |
| `cash_reconciliations` | Rekonsiliasi saldo | `cash_account_id`, `reconciliation_date`, `system_balance`, `actual_balance`, `difference`, `notes` | index akun/tanggal | akun, user | Tidak mengubah transaksi lama diam-diam. |
| `transaction_attachments` | Bukti transaksi | `operational_transaction_id`, `file_path`, `mime_type`, `file_size` | index transaksi | transaksi | File harus divalidasi dan diunduh dengan authorization. |
| `transaction_approvals` | Histori approval | `operational_transaction_id`, `status`, `notes`, `approved_by` | index transaksi/status | transaksi, user | Approval tidak boleh oleh pengaju sendiri. |
| `finance_document_sequences` | Nomor dokumen | `document_type`, `year`, `month`, `last_number` | unique type+year+month | service nomor | Di-lock untuk cegah nomor ganda. |
