<?php
$current = $_GET['page'] ?? 'dashboard';
?>

<!-- Apply stored theme IMMEDIATELY (before any CSS renders) to prevent white flash -->
<script>
(function () {
    try {
        var t = localStorage.getItem('theme');
        document.documentElement.setAttribute('data-theme', t === 'dark' ? 'dark' : 'light');
    } catch (e) { /* localStorage blocked – default light theme is fine */ }
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