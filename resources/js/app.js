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
  if (tabs.length) { activateTab(location.hash.slice(1) || 'general'); tabs.forEach((tab) => tab.addEventListener('click', () => { activateTab(tab.dataset.tab); history.replaceState(null, '', `#${tab.dataset.tab}`); })); }
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
});
