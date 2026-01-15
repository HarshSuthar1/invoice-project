/*
main.js
Global UI behavior shared across all pages
*/

import { closeModal } from './core/ui.js';
import { qs } from './core/dom.js';

// Close modals on outside click
document.addEventListener('click', (e) => {
  document.querySelectorAll('.modal.active').forEach(modal => {
    if (e.target === modal) {
      closeModal(modal.id);
    }
  });
});

// Close modals on ESC
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal.active').forEach(modal => {
      closeModal(modal.id);
    });
  }
});
