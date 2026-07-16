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

  if (!file || !target) {
    return;
  }

  target.src = URL.createObjectURL(file);
  target.classList.remove('hidden');
};

window.openModal = (name) => {
  const modal = document.getElementById(name);

  if (modal instanceof HTMLDialogElement && !modal.open) {
    modal.showModal();
  }
};

window.closeModal = (name) => {
  const modal = document.getElementById(name);

  if (modal instanceof HTMLDialogElement && modal.open) {
    modal.close();
  }
};

window.addEventListener('open-modal', (event) => {
  window.openModal(event.detail);
});

window.addEventListener('close-modal', (event) => {
  window.closeModal(event.detail);
});

document.addEventListener('click', (event) => {
  const modal = event.target;

  if (modal instanceof HTMLDialogElement && modal.matches('[data-ui-modal]')) {
    const rectangle = modal.getBoundingClientRect();
    const clickedInside = event.clientX >= rectangle.left
      && event.clientX <= rectangle.right
      && event.clientY >= rectangle.top
      && event.clientY <= rectangle.bottom;

    if (!clickedInside) {
      modal.close();
    }
  }
});
