import './bootstrap';

const html = document.documentElement;

window.toggleSidebar = () => {
  html.classList.toggle('sidebar-collapsed');
};

window.openMobileSidebar = () => {
  html.classList.add('mobile-sidebar-open');
};

window.closeMobileSidebar = () => {
  html.classList.remove('mobile-sidebar-open');
};

window.previewImage = (event, targetId) => {
  const file = event.target.files?.[0];
  const target = document.getElementById(targetId);
  if (!file || !target) return;
  const error = event.target.parentElement?.querySelector('[data-file-error]');
  const maximumKilobytes = Number(event.target.dataset.maxKb || 0);
  if (maximumKilobytes && file.size > maximumKilobytes * 1024) {
    const maximum = maximumKilobytes >= 1024 ? `${maximumKilobytes / 1024} MB` : `${maximumKilobytes} KB`;
    if (error) error.textContent = `Ukuran ${event.target.dataset.fileLabel} maksimal ${maximum}.`;
    event.target.value = '';
    return;
  }
  if (error) error.textContent = '';
  target.src = URL.createObjectURL(file);
  target.classList.remove('hidden');
};

const compressStudentPhoto = async (file, maximumBytes) => {
  const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
  let width = bitmap.width;
  let height = bitmap.height;
  const maximumDimension = 2400;
  const initialScale = Math.min(1, maximumDimension / Math.max(width, height));
  width = Math.max(1, Math.round(width * initialScale));
  height = Math.max(1, Math.round(height * initialScale));

  for (let attempt = 0; attempt < 8; attempt += 1) {
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const context = canvas.getContext('2d');
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, width, height);
    context.drawImage(bitmap, 0, 0, width, height);
    const quality = Math.max(0.45, 0.9 - (attempt * 0.08));
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
    if (!blob) throw new Error('Foto tidak dapat dikompresi oleh peramban.');
    if (blob.size <= maximumBytes) {
      bitmap.close();
      return new File([blob], `${file.name.replace(/\.[^.]+$/, '')}.jpg`, { type: 'image/jpeg', lastModified: Date.now() });
    }
    width = Math.max(1, Math.round(width * 0.82));
    height = Math.max(1, Math.round(height * 0.82));
  }

  bitmap.close();
  throw new Error('Ukuran foto masih melebihi 5 MB setelah dikompresi. Silakan ambil foto dengan resolusi lebih rendah.');
};


document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    window.closeMobileSidebar();
  }
});

document.addEventListener('DOMContentLoaded', () => {
  if (document.body.dataset.sidebarDefault === 'compact') html.classList.add('sidebar-collapsed');
  const tabs = document.querySelectorAll('[data-tab]');
  const panels = document.querySelectorAll('[data-panel]');
  const activateTab = (name) => {
    tabs.forEach((tab) => { const active = tab.dataset.tab === name; tab.classList.toggle('bg-white', active); tab.classList.toggle('text-emerald-800', active); tab.setAttribute('aria-selected', String(active)); });
    panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.panel !== name));
  };
  const settingsForm = document.querySelector('[data-settings-form]');
  if (tabs.length) { activateTab(location.hash.slice(1) || settingsForm?.dataset.initialTab || 'general'); tabs.forEach((tab) => tab.addEventListener('click', () => { activateTab(tab.dataset.tab); history.replaceState(null, '', `#${tab.dataset.tab}`); })); }
  const picker = document.querySelector('[data-color-picker]'); const hex = document.querySelector('[data-color-hex]');
  picker?.addEventListener('input', () => { hex.value = picker.value.toUpperCase(); }); hex?.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(hex.value)) picker.value = hex.value; });
  const maintenance = document.getElementById('maintenance_toggle'); const maintenanceValue = document.querySelector('[data-maintenance-value]'); const dialog = document.getElementById('maintenance-confirm');
  maintenance?.addEventListener('change', () => { if (maintenance.checked) { maintenance.checked = false; dialog?.showModal(); } else maintenanceValue.value = '0'; });
  document.querySelectorAll('[data-maintenance-cancel]').forEach((button) => button.addEventListener('click', () => dialog?.close()));
  document.querySelector('[data-maintenance-confirm]')?.addEventListener('click', () => { maintenance.checked = true; maintenanceValue.value = '1'; dialog?.close(); });
  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => {
      form.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
      });
    });
  });

  document.querySelectorAll('[data-student-photo-input]').forEach((input) => {
    input.addEventListener('change', async () => {
      const original = input.files?.[0];
      if (!original) return;
      const form = input.closest('form');
      const buttons = form?.querySelectorAll('button[type="submit"]') ?? [];
      const buttonStates = Array.from(buttons, (button) => button.disabled);
      const status = form?.querySelector('[data-student-photo-status]');
      const maximumBytes = 5 * 1024 * 1024;
      buttons.forEach((button) => { button.disabled = original.size > maximumBytes; });

      try {
        let photo = original;
        if (original.size > maximumBytes) {
          if (status) status.textContent = 'Foto lebih besar dari 5 MB. Sedang mengompresi foto…';
          photo = await compressStudentPhoto(original, maximumBytes);
          const transfer = new DataTransfer();
          transfer.items.add(photo);
          input.files = transfer.files;
          if (status) status.textContent = `Foto berhasil dikompresi menjadi ${(photo.size / 1024 / 1024).toFixed(2)} MB.`;
        } else if (status) {
          status.textContent = '';
        }

        const preview = form?.querySelector('[data-student-photo-preview]');
        if (preview) {
          preview.src = URL.createObjectURL(photo);
          preview.classList.remove('hidden');
          form.querySelector('[data-student-photo-placeholder]')?.classList.add('hidden');
        }
      } catch (error) {
        input.value = '';
        if (status) status.textContent = error instanceof Error ? error.message : 'Foto gagal dikompresi.';
      } finally {
        buttons.forEach((button, index) => { button.disabled = buttonStates[index]; });
      }
    });
  });

  const center = document.querySelector('[data-notification-center]');
  if (center) {
    const toggle = center.querySelector('[data-notification-toggle]');
    const panel = center.querySelector('[data-notification-panel]');
    const badge = center.querySelector('[data-notification-badge]');
    const list = center.querySelector('[data-notification-list]');
    const escapeHtml = (value) => { const element = document.createElement('span'); element.textContent = value ?? ''; return element.innerHTML; };
    const refresh = async () => {
      try {
        const { data } = await window.axios.get(center.dataset.url);
        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
        badge.classList.toggle('hidden', data.unread_count === 0);
        list.innerHTML = data.notifications.length ? data.notifications.map((item) => `<a href="${escapeHtml(item.url)}" data-notification-link data-read-url="${escapeHtml(item.read_url)}" class="block border-b border-slate-100 px-4 py-3 hover:bg-emerald-50 ${item.read ? '' : 'bg-emerald-50/50'}"><span class="block text-[10px] font-bold uppercase tracking-wide text-emerald-700">${escapeHtml(item.module)}</span><span class="mt-1 block text-sm font-medium text-slate-800">${escapeHtml(item.message)}</span><span class="mt-1 block text-xs text-slate-500">${item.actor ? `Oleh ${escapeHtml(item.actor)} · ` : ''}${escapeHtml(item.created_at)}</span></a>`).join('') : '<p class="p-4 text-sm text-slate-500">Belum ada notifikasi.</p>';
        list.querySelectorAll('[data-notification-link]').forEach((link) => link.addEventListener('click', async (event) => { event.preventDefault(); try { await window.axios.patch(link.dataset.readUrl); } finally { window.location.assign(link.href); } }));
      } catch { list.innerHTML = '<p class="p-4 text-sm text-rose-700">Notifikasi belum dapat dimuat.</p>'; }
    };
    toggle.addEventListener('click', () => { const opening = panel.classList.contains('hidden'); panel.classList.toggle('hidden'); toggle.setAttribute('aria-expanded', String(opening)); if (opening) refresh(); });
    center.querySelector('[data-notification-read-all]').addEventListener('click', async () => { await window.axios.patch(center.dataset.readUrl); await refresh(); });
    document.addEventListener('click', (event) => { if (!center.contains(event.target)) { panel.classList.add('hidden'); toggle.setAttribute('aria-expanded', 'false'); } });
    refresh();
    window.setInterval(refresh, Number(center.dataset.interval));
  }
});
document.querySelectorAll('[data-pmbm-wizard]').forEach((form) => {
    const steps = [...form.querySelectorAll('[data-wizard-step]')]; let current = 0;
    const previous = form.querySelector('[data-wizard-prev]'); const next = form.querySelector('[data-wizard-next]'); const submit = form.querySelector('[data-wizard-submit]'); const progress = [...form.querySelectorAll('[data-wizard-progress] span')];
    const render = () => { if (current === steps.length - 1) { const review=form.querySelector('[data-wizard-review]'); if(review) { const rows=[...form.querySelectorAll('input[name], select[name], textarea[name]')].filter(i=>i.type!=='file'&&i.type!=='checkbox'&&i.value).map(i=>`<div><b>${i.closest('label')?.querySelector('.label')?.textContent?.trim() || i.name}:</b> ${i.selectedOptions?.[0]?.textContent || i.value}</div>`); review.innerHTML=rows.join('') || '<p>Belum ada data.</p>'; } } steps.forEach((step, index) => step.hidden = index !== current); previous.hidden = current === 0; next.hidden = current === steps.length - 1; submit.hidden = current !== steps.length - 1; progress.forEach((item,index) => item.className = `rounded px-2 py-2 text-center ${index === current ? 'bg-amber-400 text-emerald-950' : index < current ? 'bg-emerald-700' : 'bg-white/15'}`); window.scrollTo({top: form.getBoundingClientRect().top + window.scrollY - 20, behavior:'smooth'}); };
    next?.addEventListener('click', () => { const inputs=steps[current].querySelectorAll('input,select,textarea'); for (const input of inputs) { if (!input.checkValidity()) { input.reportValidity(); return; } } current++; render(); }); previous?.addEventListener('click', () => { current--; render(); }); render();
});
