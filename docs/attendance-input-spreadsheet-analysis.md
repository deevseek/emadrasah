# Analisis Model Input Absensi Bulanan Siswa

Dokumen ini merangkum analisis terhadap model input absensi pada screenshot Google Sheets `ABSENSI SISWA TA. 2026-2027` untuk menjadi acuan pengembangan modul absensi siswa E-Madrasah.

## Pola Form pada Screenshot

Model input yang terlihat adalah format rekap absensi bulanan berbasis matriks:

- Satu sheet mewakili satu kelas pada satu tahun ajaran.
- Header menampilkan identitas madrasah, judul `ABSENSI SISWA BULANAN`, kelas, dan tahun ajaran.
- Pengguna memilih atau mengisi bulan terlebih dahulu.
- Baris utama berisi daftar siswa dengan kolom:
  - Nomor urut.
  - Nama siswa.
  - Jenis kelamin `L/P`.
  - Tanggal 1 sampai 31 sebagai kolom harian.
  - Rekap jumlah `S`, `I`, dan `A`.
  - Kolom keterangan.
- Sel harian diisi kode absensi singkat, terutama:
  - `S` untuk sakit.
  - `I` untuk izin.
  - `A` untuk alpha.
  - Sel kosong secara praktik spreadsheet biasanya dapat dimaknai sebagai hadir, tetapi perlu dikonfirmasi sebagai aturan sistem agar tidak menimbulkan ambiguitas.

## Perbedaan dengan Form Aplikasi Saat Ini

Form aplikasi saat ini menggunakan pola absensi harian per kelas. Pengguna memilih tanggal, lalu setiap siswa memiliki dropdown status, jam, catatan, dan bukti lampiran. Pendekatan ini kuat untuk validasi per hari, finalisasi, bukti izin/sakit, dan audit koreksi.

Model screenshot lebih berorientasi pada input cepat atau rekap bulanan. Kelebihannya adalah wali kelas dapat melihat seluruh siswa dan seluruh tanggal dalam satu layar seperti buku absensi manual. Kekurangannya adalah perlu aturan tambahan untuk hari di luar bulan, hari libur, finalisasi per tanggal, dan koreksi data final.

## Rekomendasi Bentuk Implementasi

Rekomendasi implementasi adalah menambahkan mode input bulanan di atas fondasi data harian yang sudah ada, bukan mengganti struktur absensi harian. Dengan demikian, database tetap menyimpan satu record per siswa per tanggal, sementara antarmuka dapat menampilkan grid bulanan seperti spreadsheet.

Rancangan perilaku yang disarankan:

1. Filter kelas, bulan, dan tahun ajaran ditampilkan di bagian atas.
2. Tabel menampilkan siswa sebagai baris dan tanggal 1 sampai jumlah hari dalam bulan sebagai kolom.
3. Setiap sel harian menerima kode ringkas berbasis pilihan terbatas:
   - Kosong atau `H` = hadir.
   - `S` = sakit.
   - `I` = izin.
   - `A` = alpha.
   - Opsi tambahan sistem seperti terlambat atau pulang lebih awal dapat tetap tersedia melalui detail harian atau popover agar grid utama tidak terlalu padat.
4. Kolom rekap `S`, `I`, dan `A` dihitung dari data tersimpan, bukan angka dummy.
5. Finalisasi sebaiknya tetap per tanggal atau per bulan dengan proteksi transaksi dan audit, sesuai kebutuhan operasional madrasah.
6. Lampiran izin/sakit tetap disimpan sebagai file pada storage, bukan base64 database. Pada grid, lampiran dapat dibuka melalui aksi detail per sel.

## Hal yang Perlu Dipastikan Sebelum Coding

Beberapa aturan bisnis perlu diputuskan sebelum implementasi penuh:

- Apakah sel kosong pada format spreadsheet resmi dimaknai sebagai `hadir` atau `belum diinput`.
- Apakah input bulanan boleh mengubah tanggal yang sudah difinalisasi.
- Apakah hari Ahad, hari libur, dan tanggal di luar kalender akademik harus dikunci otomatis.
- Apakah status `terlambat`, `pulang lebih awal`, `dinas`, dan `tidak dijadwalkan` tetap digunakan dalam grid bulanan atau hanya di form detail harian.
- Apakah rekap akhir hanya menghitung `S/I/A` seperti spreadsheet atau seluruh status sistem.

## Kesimpulan Teknis

Arah terbaik adalah membuat fitur `Input Absensi Bulanan` sebagai tampilan tambahan untuk wali kelas/operator. Fitur ini mempertahankan controller tipis, validasi server-side melalui Form Request, penyimpanan melalui Service/Action, dan transaksi saat menyimpan banyak sel. Pendekatan tersebut menjaga aplikasi tetap modular monolith Laravel serta konsisten dengan kebutuhan audit absensi.
