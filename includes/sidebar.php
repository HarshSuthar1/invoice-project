<?php
$current = $_GET['page'] ?? 'dashboard';
?>

<script>
/* ============================================================
   DARK MODE — fully self-contained, no ES module dependency.
   Runs synchronously so data-theme is set before first paint,
   then wires up the click handler once the button exists.
   ============================================================ */
(function () {
    var DARK = 'dark', LIGHT = 'light';

    function storedTheme() {
        try { return localStorage.getItem('theme') === DARK ? DARK : LIGHT; }
        catch (e) { return LIGHT; }
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);

        var btn   = document.getElementById('darkModeToggle');
        var icon  = document.getElementById('darkModeIcon');
        var label = document.getElementById('darkModeLabel');

        if (!btn) return;

        var isDark = (theme === DARK);
        btn.setAttribute('aria-pressed', String(isDark));
        btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        btn.title = btn.getAttribute('aria-label');
        if (icon)  icon.textContent  = isDark ? '\u2600' : '\u263E';  /* ☀ / ☾ */
        if (label) label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    }

    function wireToggle() {
        var btn = document.getElementById('darkModeToggle');
        if (!btn) return;

        /* Sync visual state of the button */
        applyTheme(storedTheme());

        btn.addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-theme');
            var next = (current === DARK) ? LIGHT : DARK;
            applyTheme(next);
            try { localStorage.setItem('theme', next); } catch (e) {}
        });
    }

    /* Step 1: Set data-theme on <html> immediately — prevents flash */
    document.documentElement.setAttribute('data-theme', storedTheme());

    /* Step 2: Wire the button once the DOM has the button in it */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wireToggle);
    } else {
        wireToggle();
    }
})();
</script>

<div class="sidebar">
    <div class="sidebar-header">
        <h1>Invoice App</h1>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="/Business%20project/public/index.php?page=dashboard"
                    class="<?= $current === 'dashboard' ? 'active' : '' ?>">
                    Dashboard
                </a>
            </li>

            <li>
                <a href="/Business%20project/public/index.php?page=clients"
                    class="<?= $current === 'clients' ? 'active' : '' ?>">
                    Clients
                </a>
            </li>

            <li>
                <a href="/Business%20project/public/index.php?page=create-hub"
                    class="<?= in_array($current, ['create-hub', 'create-document', 'invoice']) ? 'active' : '' ?>">
                    Create Documents
                </a>
            </li>

            <li>
                <a href="/Business%20project/public/index.php?page=manage-invoice"
                    class="<?= in_array($current, ['manage-invoice', 'manage-documents']) ? 'active' : '' ?>">
                    Manage Documents
                </a>
            </li>

            <li>
                <a href="/Business%20project/public/index.php?page=reports"
                    class="<?= $current === 'reports' ? 'active' : '' ?>">
                    Reports
                </a>
            </li>

            <li>
                <a href="/Business%20project/public/index.php?page=ledger"
                    class="<?= $current === 'ledger' ? 'active' : '' ?>">
                    Ledger
                </a>
            </li>

            <li>
                <a href="/Business%20project/public/index.php?page=profile"
                    class="<?= $current === 'profile' ? 'active' : '' ?>">
                    Profile
                </a>
            </li>
        </ul>
    </nav>

    <form method="post" action="/Business%20project/api/auth/logout.php" class="sidebar-actions">
        <button type="button" id="darkModeToggle" class="dark-mode-btn" aria-pressed="false">
            <span id="darkModeIcon" class="theme-icon" aria-hidden="true">&#9790;</span>
            <span id="darkModeLabel">Dark Mode</span>
        </button>
        <button type="submit" class="logout-button">Logout</button>
    </form>
</div>
