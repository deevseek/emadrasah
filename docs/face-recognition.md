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

Detektor menerima foto kamera seluler dalam empat orientasi dan otomatis mengecilkan sisi terpanjang sebelum inferensi agar YuNet tetap stabil pada foto beresolusi tinggi. Ambang deteksi dapat diatur melalui `FACE_DETECTION_SCORE_THRESHOLD` (bawaan `0.6`), sedangkan `FACE_MAX_DETECTION_DIMENSION` (bawaan `1280`) membatasi ukuran inferensi tanpa mengubah rasio foto. Ambang deteksi berbeda dari `MIN_FACE_QUALITY`: nilai pertama menentukan kandidat wajah, sedangkan nilai kedua tetap menolak wajah yang terlalu kecil atau tidak memadai. Setelah mengubah environment, restart layanan Face Recognition.

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
