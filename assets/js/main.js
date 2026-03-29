/*
  main.js
  Global bootstrap shared by every page that has a sidebar.

  ES modules are deferred by default, so this code runs after the
  HTML document is fully parsed (readyState = 'interactive' or
  'complete').  DOMContentLoaded may or may not have fired yet,
  so we handle both cases with a single, guarded init call.
*/

import { initTheme, toggleTheme } from './core/theme.js';

let initialized = false;

function initializeApp() {
    if (initialized) return;   // guard against double-calls
    initialized = true;

    // Sync the toggle button label / icon with the stored theme.
    initTheme();

    // Wire up the toggle button (absent on login / signup pages).
    const toggleBtn = document.getElementById('darkModeToggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleTheme);
    }
}

// ES modules run after HTML is parsed, so readyState is already
// 'interactive' or 'complete' – the else branch fires immediately.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp, { once: true });
} else {
    initializeApp();
}