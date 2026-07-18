# Modul 10 — Penilaian, Leger, dan Rapor

## Tujuan
Menyediakan pengelolaan konfigurasi penilaian, input nilai, verifikasi, leger kelas, generate rapor, print, dan export dengan bahasa Indonesia formal.

## Fitur
- Konfigurasi periode, komponen, KKM, predikat, dan skema nilai.
- Input nilai per komponen dan siswa melalui backend service.
- Perhitungan nilai akhir, predikat, dan ketuntasan melalui `AssessmentService`/`GradeCalculationService`.
- Alur draft, submit, verify/reject, publish/finalize, lock, dan reopen pada nilai/rapor.
- Leger web, print, dan CSV.
- Rapor web dan print dengan identitas siswa, mapel, predikat, deskripsi, kehadiran, ekstrakurikuler, prestasi, dan BTAQ bila tersedia.

## Role dan permission
Permission granular mencakup `assessment.view`, `assessment.create`, `assessment.update`, `assessment.delete`, `assessment.submit`, `assessment.verify`, `assessment.publish`, `assessment.lock`, `assessment.unlock`, `report-card.view`, `report-card.print`, dan `report-card.export`, serta permission lama `grades.*`, `grade-books.*`, `report-cards.*`.

## Alur kerja
1. Admin/operator mengelola periode, komponen, KKM, dan predikat.
2. Guru mengisi nilai draft sesuai kewenangan kelas/mapel.
3. Guru mengajukan nilai.
4. Kepala madrasah/verifikator memverifikasi atau mengembalikan dengan catatan.
5. Wali kelas generate rapor dari nilai lengkap.
6. Rapor dipublikasikan/finalisasi, dicetak, diexport, dan dikunci.

## Business rules
- Nilai siswa harus berasal dari enrollment aktif pada kelas komponen.
- Nilai tidak boleh di bawah 0 atau melebihi `maximum_score` komponen.
- Formula nilai tidak boleh diduplikasi di Blade/export.
- Rapor terkunci tidak dapat digenerate ulang kecuali di-reopen oleh permission khusus.

## Route utama
- `/assessment-module` dashboard modul.
- `/assessment-module/input` input nilai.
- `/assessment-module/leger` leger.
- `/assessment-module/reports` laporan.
- `/assessment-module/report-verification` verifikasi rapor.

## Service utama
- `App\Services\AssessmentService`
- `App\Services\Assessment\GradeCalculationService`
- `App\Services\ReportCardService`

## Export dan print
Export leger menghasilkan CSV berisi data kelas. Print leger/rapor menggunakan Blade print view A4 tanpa navigasi aplikasi.

## Known limitations
Pengujian visual browser tidak dijalankan otomatis jika environment tidak menyediakan browser automation.

## Troubleshooting
- Jika nilai tidak muncul di rapor, pastikan komponen required sudah lengkap dan semester sesuai.
- Jika akses ditolak, jalankan `php artisan db:seed --class=RolePermissionSeeder` dan bersihkan cache permission.
