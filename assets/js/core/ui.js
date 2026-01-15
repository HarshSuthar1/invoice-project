/*
ui.js
Shared UI utilities:
- modal open / close
- toast messages
*/

import { qs } from './dom.js';

/* ------------------------
   Modals
------------------------ */
export const openModal = (id) => {
  const modal = qs(`#${id}`);
  if (!modal) return;
  
  // Handle both .active class and direct style.display
  modal.classList.add('active');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
};

export const closeModal = (id) => {
  const modal = qs(`#${id}`);
  if (!modal) return;
  
  // Handle both .active class and direct style.display
  modal.classList.remove('active');
  modal.style.display = 'none';
  document.body.style.overflow = 'auto';
};

// Close modal when clicking outside (on the overlay)
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay') && e.target.classList.contains('active')) {
    closeModal(e.target.id);
  }
});

// Close modal on ESC key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const activeModals = document.querySelectorAll('.modal-overlay.active');
    activeModals.forEach(modal => {
      closeModal(modal.id);
    });
  }
});

/* ------------------------
   Toast messages
------------------------ */
export const showSuccess = (message) => {
  const el = qs('#successMessage');
  if (!el) return;

  el.textContent = message;

  const computed = window.getComputedStyle(el);
  if (computed && computed.display === 'none') {
    el.style.display = 'block';
    setTimeout(() => {
      el.style.display = 'none';
      el.textContent = '';
    }, 3000);
    return;
  }

  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 3000);
};

export const showError = (message) => {
  const el = qs('#errorMessage');
  if (!el) return;

  el.textContent = message;

  // If the element is an inline error block that uses display:none to hide,
  // show it via style.display so old pages (login/signin) still work.
  const computed = window.getComputedStyle(el);
  if (computed && computed.display === 'none') {
    el.style.display = 'block';
    setTimeout(() => {
      el.style.display = 'none';
      el.textContent = '';
    }, 3000);
    return;
  }

  // Otherwise use the toast-style show/hide by toggling a class
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 3000);
};