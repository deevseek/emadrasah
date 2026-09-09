# Face Recognition HRD

Layanan di `face-recognition-service/` menggunakan Python 3.11+, FastAPI, OpenCV YuNet untuk deteksi, dan OpenCV SFace (`face_recognition_sface_2021dec.onnx`) untuk embedding 128 dimensi pada CPU. Unduh model resmi OpenCV Zoo `face_detection_yunet_2023mar.onnx` dan `face_recognition_sface_2021dec.onnx` ke direktori `models/`; model besar sengaja tidak disimpan di Git.

## Instalasi Ubuntu

```bash
cd face-recognition-service
python3 -m venv .venv
. .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env # muat variabel ini melalui systemd/shell
uvicorn app.main:app --host "${FACE_API_HOST:-127.0.0.1}" --port "${FACE_API_PORT:-8791}"
curl http://127.0.0.1:8791/health
pytest
```

Konfigurasikan Laravel dengan `FACE_RECOGNITION_DRIVER=python`, URL localhost, token internal yang identik, dan timeout. Browser hanya mengirim capture ke Laravel; endpoint internal `POST /v1/faces/encode` dan `POST /v1/faces/verify` dilindungi Bearer token. `GET /health` tidak memuat data biometrik.

SFace menghasilkan embedding ternormalisasi. Verifikasi memakai cosine similarity (rentang praktis -1..1; semakin besar semakin mirip) terhadap masing-masing dari tiga embedding acuan dan mengambil nilai maksimum. Setting `hrd_face_confidence_threshold` adalah ambang langsung metric tersebut dan harus dikalibrasi dengan data uji legal. Engine ini tidak menyediakan liveness; API mengembalikan `liveness_supported=false` dan `liveness_passed=null`.

Detektor menerima foto kamera seluler dalam empat orientasi dan otomatis mengecilkan sisi terpanjang sebelum inferensi agar YuNet tetap stabil pada foto beresolusi tinggi. Ambang deteksi dapat diatur melalui `FACE_DETECTION_SCORE_THRESHOLD` (bawaan `0.45`), sedangkan `FACE_MAX_DETECTION_DIMENSION` (bawaan `1280`) membatasi ukuran inferensi tanpa mengubah rasio foto. `MIN_FACE_QUALITY` (bawaan `0.35`) memeriksa confidence kandidat dan `MIN_FACE_AREA_RATIO` (bawaan `0.025`) memastikan wajah tidak terlalu jauh dari kamera. Pemisahan pemeriksaan ukuran dan confidence ini menghindari penalti ganda pada wajah yang tetap valid ketika kotak deteksinya sedikit lebih kecil, termasuk saat menggunakan penutup kepala. Setelah mengubah environment, restart layanan Face Recognition.

Setting threshold pada aplikasi hanya mengatur kecocokan embedding dan tidak memengaruhi deteksi atau kualitas foto. Jangan menurunkannya untuk menangani pesan wajah tidak terdeteksi atau kualitas rendah. Untuk instalasi lama yang sudah menetapkan environment secara eksplisit, sesuaikan ketiga nilai di atas, restart layanan, lalu daftarkan ulang wajah dengan penampilan yang lazim digunakan saat absensi.

Embedding disimpan terenkripsi oleh Laravel (`encrypted:array`) dan foto disimpan pada disk private di `storage/app/private/personnel-faces`, hanya disajikan controller berizin. Snapshot absensi tidak disimpan.

## systemd

Salin contoh berikut ke `/etc/systemd/system/emadrasah-face-recognition.service`, sesuaikan pengguna/path, simpan rahasia di `/etc/emadrasah/face.env`, lalu jalankan `systemctl enable --now emadrasah-face-recognition`. Log tersedia melalui `journalctl -u emadrasah-face-recognition`.

```ini
[Unit]
Description=e-Madrasah Face Recognition
After=network.target
[Service]
User=www-data
WorkingDirectory=/var/www/emadrasah/face-recognition-service
EnvironmentFile=/etc/emadrasah/face.env
ExecStart=/var/www/emadrasah/face-recognition-service/.venv/bin/uvicorn app.main:app --host ${FACE_API_HOST} --port ${FACE_API_PORT}
Restart=on-failure
NoNewPrivileges=true
PrivateTmp=true
[Install]
WantedBy=multi-user.target
```

Jika Laravel dan API berada pada server yang sama, bind ke `127.0.0.1`; Nginx/tunnel tidak diperlukan. Health `unavailable` berarti model/path belum siap. Jika fitur diwajibkan, kegagalan API menolak absensi dan tidak melakukan bypass.

## Restart dari Pengaturan HRD

Tombol **Mulai Ulang Layanan** pada status Face Recognition hanya tersedia bagi pengguna dengan izin `hrd-settings.update`. Laravel menjalankan perintah lokal yang ditetapkan melalui `FACE_RECOGNITION_RESTART_COMMAND`; perintah tersebut tidak dapat diubah dari browser atau database.

Untuk systemd, berikan hak `sudo` yang terbatas kepada pengguna PHP-FPM/web server, misalnya melalui `/etc/sudoers.d/emadrasah-face-recognition`:

```sudoers
www-data ALL=(root) NOPASSWD: /usr/bin/systemctl restart emadrasah-face-recognition.service
```

Kemudian atur environment Laravel berikut dan bangun ulang cache konfigurasi:

```dotenv
FACE_RECOGNITION_RESTART_COMMAND="sudo /usr/bin/systemctl restart emadrasah-face-recognition.service"
FACE_RECOGNITION_RESTART_TIMEOUT=30
```

Jangan memberikan akses `systemctl` umum atau shell tanpa batas kepada `www-data`. Setiap restart yang berhasil dicatat pada activity log HRD, sedangkan kegagalan hanya menampilkan pesan umum agar keluaran proses dan detail server tidak bocor ke browser.

## Pemindaian langsung dan verifikasi burst

Absensi mandiri memakai kamera depan browser (`getUserMedia`) sebagai pratinjau langsung. Setelah perangkat diperiksa dan challenge baru dibuat, browser mengambil lima JPEG secara otomatis dengan jeda 200 ms. Sisi terpanjang dibatasi 1280 piksel dan kualitas JPEG 0,88. CSS membalik **pratinjau saja**; `canvas.drawImage(video, ...)` mengambil piksel sumber tanpa pembalikan sehingga enrollment dan absensi konsisten. Video tidak dikirim dan bukan streaming ke server.

Endpoint internal `POST /v1/faces/verify-burst` menerima maksimal lima frame multipart. YuNet menganalisis tepat satu wajah, confidence detector, rasio area, luminansi, dan variasi Laplacian. Gate default tetap terpisah: detection `0.45`, minimum area `0.025`, quality detector `0.35`, luminansi `35–225`, dan blur `18`. Nilai ini sengaja hanya menolak kondisi yang jelas buruk dan dapat dikonfigurasi melalui environment. Tiga frame valid dengan skor kualitas gabungan terbaik dipilih. Setiap frame dibandingkan dengan **semua** embedding referensi SFace.

Keputusan menggunakan mayoritas: sedikitnya dua dari tiga frame terpilih harus melewati threshold identitas. Bila hanya dua frame valid, keduanya wajib cocok; kurang dari dua frame menghasilkan `INSUFFICIENT_VALID_FRAMES`. Confidence akhir adalah median confidence frame terpilih, bukan nilai maksimum yang rentan outlier. Hanya frame terbaik yang cocok (gabungan quality dan confidence) disimpan pada disk privat sebagai bukti. Ringkasan confidence, jumlah frame valid, dan jumlah frame cocok disimpan untuk audit petugas; embedding tidak pernah dikirim ke browser atau log.

## Enrollment dan kompatibilitas

Enrollment baru memandu lima capture: dua netral depan, satu ekspresi natural, sedikit kiri, dan sedikit kanan. Semua embedding diuji konsistensi cosine secara konservatif sebelum transaksi penyimpanan. Tabel sampel yang ada sudah one-to-many sehingga tidak dibutuhkan migrasi untuk menambah sampel. Profil lama dengan tiga sampel tetap menjadi referensi yang sah dan tidak wajib mendaftar ulang; daftar ulang disarankan untuk memperoleh variasi terbaru.

## Threshold dan kalibrasi

Default `hrd_face_confidence_threshold` berubah dari `0.80` menjadi `0.50` hanya saat setting belum tersimpan. Migration tidak mengubah nilai administrator yang sudah ada. Threshold identity SFace ini berbeda dari threshold detection YuNet dan quality gate.

Angka 0,50 adalah titik awal, bukan optimum universal. Untuk kalibrasi, kumpulkan genuine attempts dalam kondisi sekolah dan impostor/non-match attempts yang diperoleh secara legal; bandingkan distribusi cosine similarity; lalu pilih threshold berdasarkan toleransi false reject dan false accept organisasi. Jangan memilih threshold hanya karena satu pengguna berhasil.

## Liveness, privasi, dan operasional

Kamera langsung tidak sama dengan anti-spoof/liveness. Respons tetap `liveness_supported=false` dan `liveness_passed=null`. Challenge, session/device binding, anti-replay, TTL 60 detik, lokasi, CSRF, permission, serta throttle tetap diproses Laravel. Foto dan embedding tidak dikirim ke pihak ketiga; foto bukti berada di disk privat dan embedding memakai cast terenkripsi. Layanan Python harus tetap di jaringan internal (default `127.0.0.1`). Setelah mengubah environment quality gate/model, mulai ulang layanan Face Recognition agar konfigurasi dimuat kembali.
