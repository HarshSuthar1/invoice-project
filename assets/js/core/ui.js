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
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
};

export const closeModal = (id) => {
  const modal = qs(`#${id}`);
  if (!modal) return;
  modal.classList.remove('active');
  document.body.style.overflow = 'auto';
};

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
