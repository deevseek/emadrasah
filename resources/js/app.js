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
