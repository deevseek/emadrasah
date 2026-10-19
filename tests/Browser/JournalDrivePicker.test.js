import test from 'node:test';
import assert from 'node:assert/strict';
import { driveDownloadPlan, importDriveFile, readLimitedBlob } from '../../public/js/journal-drive-picker.js';

test('file PDF yang dipilih diimpor memakai token hanya ke Google', async () => {
    const calls = [];
    const fetcher = async (url, options) => {
        calls.push({ url, options });
        return calls.length === 1
            ? Response.json({ name: 'Tugas.pdf', mimeType: 'application/pdf', size: '14', capabilities: { canDownload: true } })
            : new Response('%PDF-1.4 tugas');
    };
    const file = await importDriveFile('file_123', 'token-sementara', fetcher);
    assert.equal(file.name, 'Tugas.pdf');
    assert.equal(file.type, 'application/pdf');
    assert.equal(await file.text(), '%PDF-1.4 tugas');
    assert.equal(calls[1].url, 'https://www.googleapis.com/drive/v3/files/file_123?alt=media');
    assert.ok(calls.every(call => new URL(call.url).host === 'www.googleapis.com'));
    assert.ok(calls.every(call => call.options.headers.Authorization === 'Bearer token-sementara'));
    assert.ok(calls.every(call => !call.url.includes('token-sementara')));
});

test('dokumen Google diekspor sebagai file Office sebelum diunggah', async () => {
    const calls = [];
    const fetcher = async url => {
        calls.push(url);
        return calls.length === 1
            ? Response.json({ name: 'Latihan', mimeType: 'application/vnd.google-apps.document' })
            : new Response('isi dokumen');
    };
    const file = await importDriveFile('document-123', 'token', fetcher);
    assert.equal(file.name, 'Latihan.docx');
    assert.match(calls[1], /\/export\?mimeType=application%2Fvnd.openxmlformats/);
    assert.equal(file.type, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    assert.equal(driveDownloadPlan({ name: 'Nilai', mimeType: 'application/vnd.google-apps.spreadsheet' }).name, 'Nilai.xlsx');
    assert.equal(driveDownloadPlan({ name: 'Materi', mimeType: 'application/vnd.google-apps.presentation' }).name, 'Materi.pptx');
});

test('file terlalu besar, tidak diizinkan, dan format berbahaya ditolak', () => {
    assert.throws(() => driveDownloadPlan({ name: 'besar.pdf', size: 10485761 }), /10 MB/);
    assert.throws(() => driveDownloadPlan({ name: 'privat.pdf', capabilities: { canDownload: false } }), /tidak mengizinkan/);
    assert.throws(() => driveDownloadPlan({ name: 'script.php', mimeType: 'application/x-php' }), /didukung/);
    assert.throws(() => driveDownloadPlan({ name: 'Folder', mimeType: 'application/vnd.google-apps.folder' }), /didukung/);
});

test('batas ukuran tetap diperiksa ketika server tidak mengirim Content-Length', async () => {
    let cancelled = false;
    const stream = new ReadableStream({
        start(controller) { controller.enqueue(new Uint8Array(10485761)); },
        cancel() { cancelled = true; },
    });
    await assert.rejects(readLimitedBlob(new Response(stream), 'application/pdf'), /10 MB/);
    assert.equal(cancelled, true);
});

test('akses Drive ditolak dan identitas file tidak valid tidak diteruskan', async () => {
    await assert.rejects(importDriveFile('file123', 'kedaluwarsa', async () => new Response('', { status: 401 })), /Login ulang/);
    let called = false;
    await assert.rejects(importDriveFile('../metadata', 'token', async () => { called = true; }), /Identitas/);
    assert.equal(called, false);
});

async function mockPickerBrowser(callback) {
    const { initializeDrivePicker } = await import('../../public/js/journal-drive-picker.js');
    const previous = { document: globalThis.document, window: globalThis.window, DataTransfer: globalThis.DataTransfer, fetch: globalThis.fetch };
    const button = { disabled: true, addEventListener(event, handler) { this[event] = handler; } };
    const input = { files: [], addEventListener() {} };
    const submit = {};
    const status = {};
    const list = { replaceChildren() {}, append() {} };
    let tokenOptions;
    let selection;
    let loginRequested = false;
    const form = {
        dataset: {}, addEventListener() {}, querySelectorAll: () => [],
        querySelector: selector => ({ '[name="assignment_files[]"]': input, '[data-journal-submit]': submit, '[name="classroom_id"]': { value: '1' } }[selector]),
    };
    const root = {
        dataset: { clientId: 'test.apps.googleusercontent.com', apiKey: 'browser-key', appId: '123', existingCount: '0' },
        closest: () => form,
        querySelector: selector => ({ '[data-drive-select]': button, '[data-drive-status]': status, '[data-selected-assignment-files]': list }[selector]),
    };
    class Builder {
        addView() { return this; } enableFeature() { return this; } setOAuthToken() { return this; }
        setDeveloperKey() { return this; } setAppId() { return this; } setOrigin() { return this; }
        setTitle() { return this; } setCallback(fn) { selection = fn; return this; }
        build() { return { setVisible() {}, dispose() {} }; }
    }
    class View { setIncludeFolders() { return this; } setSelectFolderEnabled() { return this; } }
    globalThis.document = { createElement: () => ({}), head: { append: script => queueMicrotask(() => script.onload()) } };
    globalThis.window = {
        isSecureContext: true, location: { origin: 'https://madrasah.example' },
        gapi: { load: (name, options) => options.callback() },
        google: {
            accounts: { oauth2: {
                hasGrantedAllScopes: () => true,
                initTokenClient(options) {
                    tokenOptions = options;
                    return { requestAccessToken() { loginRequested = true; options.callback({ access_token: 'token-sementara' }); } };
                },
            } },
            picker: { Action: { PICKED: 'picked', CANCEL: 'cancel' }, DocsView: View, PickerBuilder: Builder, ViewId: { DOCS: 'docs' }, Feature: { MULTISELECT_ENABLED: 'multiple' } },
        },
    };
    globalThis.DataTransfer = class {
        files = [];
        items = { add: file => this.files.push(file) };
    };
    try {
        await initializeDrivePicker(root);
        await callback({ button, input, form, status, tokenOptions, select: value => selection(value), loggedIn: () => loginRequested });
    } finally {
        Object.assign(globalThis, previous);
    }
}

test('tombol login membuka Picker lalu menggabungkan file Drive dengan file komputer', async () => {
    await mockPickerBrowser(async ({ button, input, form, tokenOptions, select, loggedIn }) => {
        assert.equal(button.disabled, false);
        assert.equal(tokenOptions.scope, 'https://www.googleapis.com/auth/drive.file');
        input.files = [new File(['lokal'], 'komputer.txt', { type: 'text/plain' })];
        globalThis.fetch = async url => url.includes('?fields=')
            ? Response.json({ name: 'Drive.pdf', mimeType: 'application/pdf', size: '4' })
            : new Response('%PDF');
        button.click();
        assert.equal(loggedIn(), true);
        assert.equal(form.dataset.driveBusy, 'true');
        await select({ action: 'picked', docs: [{ id: 'file_123' }] });
        assert.equal(form.dataset.driveBusy, 'false');
        assert.deepEqual(input.files.map(file => file.name), ['komputer.txt', 'Drive.pdf']);
    });
});

test('pembatalan Picker dan popup login melepaskan blokir penyimpanan', async () => {
    await mockPickerBrowser(async ({ button, form, tokenOptions, select, status }) => {
        button.click();
        await select({ action: 'cancel' });
        assert.equal(form.dataset.driveBusy, 'false');
        assert.match(status.textContent, /dibatalkan/);
        button.click();
        tokenOptions.error_callback({ type: 'popup_closed' });
        assert.equal(form.dataset.driveBusy, 'false');
        assert.equal(button.disabled, false);
        assert.match(status.textContent, /ditutup atau diblokir/);
    });
});
