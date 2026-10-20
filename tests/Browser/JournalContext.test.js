import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const view = readFileSync(new URL('../../resources/views/academic/teaching-journals/form.blade.php', import.meta.url), 'utf8');
// Simulasikan URL route server di belakang proxy yang melihat koneksi HTTP.
const script = view.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace(/\{\{ \$journal->exists \? route\([^\n]+? \}\}/g, 'http://internal-server/academic/teaching-journals/create');

function journalPage({ responseOk = true, pageUrl = 'https://madrasah.example/academic/teaching-journals/create' } = {}) {
    const element = (value = '') => ({
        value, dataset: {}, disabled: false, hidden: false, textContent: '', innerHTML: '',
        classList: { toggle() {}, add() {}, remove() {} },
        addEventListener(type, listener) { this[type] = listener; },
    });
    const form = element();
    let destroyed = false;
    Object.defineProperty(form, 'textContent', { set() { destroyed = true; } });
    const fields = Object.fromEntries(Object.entries({
        academic_year_id: '1', semester_id: '1', journal_date: '2026-10-20',
        classroom_id: '1', academic_subject_id: '10',
    }).map(([name, value]) => [name, Object.assign(element(value), { name, options: [{ value }] })]));
    const lesson = element('3-4');
    const status = element();
    const submit = element();
    const attendance = element();
    const rosterInput = element();
    const roster = Object.assign(element(), {
        dataset: { classroom: '1' },
        querySelectorAll(selector) { return selector === 'input,select' ? [rosterInput] : []; },
    });
    const selectors = {
        '[data-context-status]': status,
        '[data-manual-help]': element(), '[data-auto-help]': element(),
        '#journal-form [data-journal-submit]': submit,
        ...Object.fromEntries(Object.entries(fields).map(([name, field]) => [`[name="${name}"]`, field])),
    };
    // dataset.contextLoading adds data-context-loading to the form before its status paragraph.
    const document = {
        getElementById(id) {
            if (destroyed && id !== 'journal-form') return null;
            return { 'journal-form': form, 'journal-classroom': fields.classroom_id,
                'journal-lesson-number': lesson, 'journal-attendance-context': attendance }[id];
        },
        querySelector(selector) {
            if (selector === '[data-context-loading]' && form.dataset.contextLoading) return form;
            return destroyed ? null : selectors[selector];
        },
        querySelectorAll(selector) {
            if (destroyed) return [];
            if (selector === '.attendance-roster') return [roster];
            return [fields.academic_year_id, fields.semester_id, fields.journal_date, fields.classroom_id];
        },
    };
    const nextFields = {
        semester_id: { value: '1', innerHTML: '<option value="1">Ganjil</option>' },
        classroom_id: { value: '2', innerHTML: '<option value="2">Rombel B</option>' },
        academic_subject_id: { value: '20', innerHTML: '<option value="20">Matematika</option>' },
    };
    let pending;
    const requests = [];
    vm.runInNewContext(script, {
        document, URLSearchParams,
        setTimeout(callback) { pending = callback; }, clearTimeout() {},
        fetch: async (url, options) => {
            const resolved = new URL(url, pageUrl);
            requests.push({ url: resolved.href, options });
            if (resolved.origin !== new URL(pageUrl).origin) throw new TypeError('Failed to fetch');
            return { ok: responseOk, redirected: false, text: async () => '<html></html>' };
        },
        DOMParser: class {
            parseFromString() {
                return {
                    querySelector: selector => nextFields[selector.match(/name="([^"]+)"/)[1]],
                    getElementById: () => ({ innerHTML: 'Daftar siswa rombel B' }),
                };
            }
        },
    });
    return { fields, form, status, submit, attendance, requests,
        destroyed: () => destroyed, flush: () => pending() };
}

test('guru mengganti rombel: formulir tetap utuh dan absensi serta mapel diperbarui', async () => {
    const page = journalPage();
    page.fields.classroom_id.value = '2';
    page.fields.classroom_id.change({ target: page.fields.classroom_id });
    assert.equal(page.destroyed(), false);
    assert.equal(page.form.dataset.contextLoading, 'true');
    assert.equal(page.submit.disabled, true);
    assert.match(page.status.textContent, /Memuat absensi/);
    await page.flush();
    assert.equal(page.destroyed(), false);
    assert.equal(page.form.dataset.contextLoading, 'false');
    assert.equal(page.submit.disabled, false);
    assert.equal(page.attendance.innerHTML, 'Daftar siswa rombel B');
    assert.match(page.fields.academic_subject_id.innerHTML, /Matematika/);
    assert.match(page.requests[0].url, /classroom_id=2/);
    assert.equal(page.requests[0].options.headers['X-Journal-Context'], '1');
});

test('kegagalan pemuatan menampilkan pesan pada status tanpa menghapus formulir', async () => {
    const page = journalPage({ responseOk: false });
    page.fields.classroom_id.change({ target: page.fields.classroom_id });
    await page.flush();
    assert.equal(page.destroyed(), false);
    assert.match(page.status.textContent, /Konteks tidak dapat dimuat/);
    assert.equal(page.submit.disabled, true);
});

for (const path of ['/academic/teaching-journals/create', '/academic/teaching-journals/7/edit']) {
    test(`pemuatan memakai HTTPS dan alamat halaman aktif di belakang proxy: ${path}`, async () => {
        const pageUrl = `https://madrasah.example${path}?classroom_id=1`;
        const page = journalPage({ pageUrl });
        page.fields.classroom_id.value = '2';
        page.fields.classroom_id.change({ target: page.fields.classroom_id });
        await page.flush();
        const requested = new URL(page.requests[0].url);
        assert.equal(requested.origin, 'https://madrasah.example');
        assert.equal(requested.pathname, path);
        assert.equal(requested.searchParams.get('classroom_id'), '2');
        assert.equal(page.form.dataset.contextLoading, 'false');
        assert.equal(page.submit.disabled, false);
    });
}
