const MAX_BYTES = 10 * 1024 * 1024;
const MAX_FILES = 10;
const ALLOWED_EXTENSIONS = /\.(pdf|docx?|xlsx?|pptx?|jpe?g|png|txt)$/i;
const EXPORTS = {
    'application/vnd.google-apps.document': ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx'],
    'application/vnd.google-apps.spreadsheet': ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx'],
    'application/vnd.google-apps.presentation': ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'pptx'],
    'application/vnd.google-apps.drawing': ['application/pdf', 'pdf'],
};

export function driveDownloadPlan(metadata) {
    if (metadata.capabilities?.canDownload === false) throw new Error('Pemilik file tidak mengizinkan file ini diunduh.');
    if (Number(metadata.size || 0) > MAX_BYTES) throw new Error('Ukuran setiap file tugas maksimal 10 MB.');
    let name = String(metadata.name || 'Tugas').replace(/[/\\\u0000-\u001f\u007f]/g, '_').slice(0, 200);
    const converted = EXPORTS[metadata.mimeType];
    if (converted) {
        const [mime, extension] = converted;
        if (!name.toLowerCase().endsWith(`.${extension}`)) name += `.${extension}`;
        return { name, mime, query: `/export?mimeType=${encodeURIComponent(mime)}` };
    }
    if (String(metadata.mimeType || '').startsWith('application/vnd.google-apps.') || !ALLOWED_EXTENSIONS.test(name)) {
        throw new Error('Pilih file PDF, Word, Excel, PowerPoint, JPG, PNG, atau TXT yang didukung.');
    }
    return { name, mime: metadata.mimeType || 'application/octet-stream', query: '?alt=media' };
}

export async function readLimitedBlob(response, mime) {
    if (Number(response.headers.get('Content-Length') || 0) > MAX_BYTES) {
        await response.body?.cancel();
        throw new Error('Ukuran setiap file tugas maksimal 10 MB.');
    }
    if (!response.body) throw new Error('File Google Drive tidak dapat dibaca oleh peramban.');
    const reader = response.body.getReader();
    const chunks = [];
    let size = 0;
    try {
        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            size += value.byteLength;
            if (size > MAX_BYTES) {
                await reader.cancel();
                throw new Error('Ukuran setiap file tugas maksimal 10 MB.');
            }
            chunks.push(value);
        }
    } finally {
        reader.releaseLock();
    }
    return new Blob(chunks, { type: mime });
}

export async function importDriveFile(id, accessToken, fetcher = fetch) {
    if (!/^[A-Za-z0-9_-]+$/.test(id)) throw new Error('Identitas file Google Drive tidak valid.');
    const base = `https://www.googleapis.com/drive/v3/files/${encodeURIComponent(id)}`;
    const headers = { Authorization: `Bearer ${accessToken}` };
    const metadataResponse = await fetcher(`${base}?fields=id,name,mimeType,size,capabilities(canDownload)&supportsAllDrives=true`, { headers });
    if (!metadataResponse.ok) throw new Error('File tidak dapat diakses. Login ulang atau pilih file yang diizinkan untuk akun Anda.');
    const plan = driveDownloadPlan(await metadataResponse.json());
    const response = await fetcher(`${base}${plan.query}`, { headers });
    if (!response.ok) throw new Error('File gagal diambil dari Google Drive. Periksa izin unduh dan coba kembali.');
    const blob = await readLimitedBlob(response, plan.mime);
    return new File([blob], plan.name, { type: plan.mime });
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        const timer = setTimeout(() => reject(new Error('Google tidak merespons. Periksa koneksi lalu muat ulang halaman.')), 20000);
        script.src = src;
        script.async = true;
        script.onload = () => { clearTimeout(timer); resolve(); };
        script.onerror = () => { clearTimeout(timer); reject(new Error('Layanan Google gagal dimuat. Periksa koneksi lalu muat ulang halaman.')); };
        document.head.append(script);
    });
}

export async function initializeDrivePicker(root) {
    const form = root.closest('form');
    const button = root.querySelector('[data-drive-select]');
    const status = root.querySelector('[data-drive-status]');
    const list = root.querySelector('[data-selected-assignment-files]');
    const input = form.querySelector('[name="assignment_files[]"]');
    const submit = form.querySelector('[data-journal-submit]');
    const configuration = root.dataset;
    const showFiles = () => {
        list.replaceChildren();
        for (const file of input.files) {
            const item = document.createElement('li');
            item.textContent = `${file.name} (${Math.ceil(file.size / 1024)} KB)`;
            list.append(item);
        }
    };
    input.addEventListener('change', showFiles);
    if (!configuration.clientId || !configuration.apiKey || !configuration.appId) return;
    if (!window.isSecureContext || typeof DataTransfer === 'undefined') {
        status.textContent = 'Pemilih Google Drive memerlukan HTTPS dan peramban terbaru.';
        return;
    }
    const setBusy = (busy) => {
        form.dataset.driveBusy = String(busy);
        button.disabled = busy;
        input.disabled = busy;
        submit.disabled = busy || form.dataset.contextLoading === 'true' || !form.querySelector('[name="classroom_id"]').value;
    };
    form.addEventListener('submit', event => {
        if (form.dataset.driveBusy === 'true') event.preventDefault();
    });
    try {
        await Promise.all([
            loadScript('https://accounts.google.com/gsi/client'),
            loadScript('https://apis.google.com/js/api.js'),
        ]);
        await new Promise((resolve, reject) => window.gapi.load('picker', {
            callback: resolve,
            onerror: () => reject(new Error('Google Picker gagal dimuat. Muat ulang halaman untuk mencoba kembali.')),
            timeout: 15000,
            ontimeout: () => reject(new Error('Google Picker tidak merespons. Muat ulang halaman untuk mencoba kembali.')),
        }));
        const client = window.google.accounts.oauth2.initTokenClient({
            client_id: configuration.clientId,
            scope: 'https://www.googleapis.com/auth/drive.file',
            callback: token => {
                if (token.error || !token.access_token) {
                    status.textContent = 'Login Google tidak berhasil atau akses belum diberikan. Silakan coba kembali.';
                    setBusy(false);
                    return;
                }
                if (!window.google.accounts.oauth2.hasGrantedAllScopes(token, 'https://www.googleapis.com/auth/drive.file')) {
                    status.textContent = 'Izin membaca file yang dipilih diperlukan untuk mengimpor lampiran.';
                    setBusy(false);
                    return;
                }
                let accessToken = token.access_token;
                const pickerApi = window.google.picker;
                let picker;
                const dispose = () => { picker?.dispose?.(); picker = null; accessToken = null; };
                const callback = async result => {
                    if (result.action === pickerApi.Action.CANCEL) {
                        dispose();
                        status.textContent = 'Pemilihan dibatalkan. File yang sudah dipilih tetap tersedia.';
                        setBusy(false);
                        return;
                    }
                    if (result.action !== pickerApi.Action.PICKED) return;
                    picker?.setVisible(false);
                    try {
                        const selected = result.docs || [];
                        const existing = Number(configuration.existingCount || 0) - form.querySelectorAll('[name="remove_assignment_attachments[]"]:checked').length;
                        const linkCount = form.querySelector('[name="assignment_drive_url"]')?.value.trim() ? 1 : 0;
                        if (!selected.length || existing + input.files.length + selected.length + linkCount > MAX_FILES) throw new Error('Maksimal 10 lampiran tugas untuk setiap jurnal.');
                        const imported = [];
                        for (const [index, document] of selected.entries()) {
                            status.textContent = `Mengambil file ${index + 1} dari ${selected.length} dari Google Drive…`;
                            imported.push(await importDriveFile(document.id, accessToken));
                        }
                        const transfer = new DataTransfer();
                        for (const file of [...input.files, ...imported]) transfer.items.add(file);
                        input.files = transfer.files;
                        showFiles();
                        status.textContent = `${imported.length} file dari Google Drive siap diunggah. Tekan Simpan Jurnal untuk menyimpan lampiran.`;
                    } catch (error) {
                        status.textContent = error.message || 'Impor file Google Drive gagal. Silakan coba kembali.';
                    } finally {
                        dispose();
                        setBusy(false);
                    }
                };
                try {
                    const view = new pickerApi.DocsView(pickerApi.ViewId.DOCS).setIncludeFolders(true).setSelectFolderEnabled(false);
                    picker = new pickerApi.PickerBuilder().addView(view)
                        .enableFeature(pickerApi.Feature.MULTISELECT_ENABLED)
                        .setOAuthToken(accessToken).setDeveloperKey(configuration.apiKey)
                        .setAppId(configuration.appId).setOrigin(window.location.origin)
                        .setCallback(callback).setTitle('Pilih File Tugas dari Google Drive').build();
                    picker.setVisible(true);
                    status.textContent = 'Pilih file tugas pada jendela Google Drive.';
                } catch {
                    dispose();
                    status.textContent = 'Pemilih file gagal dibuka. Hubungi operator untuk memeriksa konfigurasi Google Cloud.';
                    setBusy(false);
                }
            },
            error_callback: () => {
                status.textContent = 'Jendela login Google ditutup atau diblokir. Izinkan popup lalu coba kembali.';
                setBusy(false);
            },
        });
        button.addEventListener('click', () => {
            setBusy(true);
            status.textContent = 'Menunggu login dan izin Google…';
            try { client.requestAccessToken({ prompt: 'select_account' }); }
            catch { status.textContent = 'Login Google gagal dibuka. Izinkan popup lalu coba kembali.'; setBusy(false); }
        });
        button.disabled = false;
        status.textContent = 'Klik Pilih dari Google Drive untuk login dan memilih file tugas.';
    } catch (error) {
        status.textContent = error.message;
    }
}

if (typeof document !== 'undefined') {
    for (const root of document.querySelectorAll('[data-google-drive-picker]')) initializeDrivePicker(root);
}
