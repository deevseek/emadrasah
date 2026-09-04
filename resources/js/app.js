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
