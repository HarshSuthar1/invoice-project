/*
  theme.js
  Handles dark / light mode toggle.

  The stored key is 'theme'; values are 'dark' | 'light'.
  The data-theme attribute is set on <html> so CSS variables
  defined under html[data-theme="dark"] kick in automatically.

  FOUC prevention lives in sidebar.php (inline script that reads
  localStorage synchronously before the page body renders).
*/

const THEME_KEY  = 'theme';
const DARK       = 'dark';
const LIGHT      = 'light';

/* ── helpers ───────────────────────────────────────────── */

function stored() {
    try   { return localStorage.getItem(THEME_KEY) === DARK ? DARK : LIGHT; }
    catch { return LIGHT; }
}

function save(theme) {
    try   { localStorage.setItem(THEME_KEY, theme); }
    catch { /* private / blocked storage – ignore */ }
}

function current() {
    return document.documentElement.getAttribute('data-theme') === DARK ? DARK : LIGHT;
}

/* ── DOM update ─────────────────────────────────────────── */

function syncButton(theme) {
    const btn   = document.getElementById('darkModeToggle');
    const icon  = document.getElementById('darkModeIcon');
    const label = document.getElementById('darkModeLabel');

    if (!btn) return;   // login / signup pages have no sidebar

    const isDark = theme === DARK;

    btn.setAttribute('aria-pressed', String(isDark));
    btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
    btn.title = btn.getAttribute('aria-label');

    if (icon)  icon.textContent  = isDark ? '\u2600' : '\u263E';   // ☀ / ☾
    if (label) label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    syncButton(theme);
}

/* ── public API ─────────────────────────────────────────── */

/**
 * Call once on page load: reads localStorage and syncs the button label.
 * The attribute is already set by the inline FOUC-prevention script in
 * sidebar.php, so this only needs to update the button UI.
 */
export function initTheme() {
    applyTheme(stored());
}

/**
 * Toggle between light ↔ dark, persist the choice, and update the button.
 */
export function toggleTheme() {
    const next = current() === DARK ? LIGHT : DARK;
    applyTheme(next);
    save(next);
}