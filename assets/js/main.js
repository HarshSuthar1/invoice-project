/*
main.js
Global UI behavior shared across all pages
*/
import { initTheme, toggleTheme } from './core/theme.js';

const initializeApp = () => {
    initTheme();

    const darkModeToggle = document.getElementById('darkModeToggle');

    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', toggleTheme);
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp, { once: true });
} else {
    initializeApp();
}
