<?php
$current = $_GET['page'] ?? 'dashboard';
?>

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
                <a href="/Business%20project/public/index.php?page=invoice"
                   class="<?= $current === 'invoice' ? 'active' : '' ?>">
                   Create Invoice
                </a>
            </li>

            <li>
                <a href="/Business%20project/public/index.php?page=manage-invoice"
                   class="<?= $current === 'manage-invoice' ? 'active' : '' ?>">
                   Manage Invoices
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

    <form method="post" action="/Business%20project/api/auth/logout.php">
        <button type="submit" class="logout-button">Logout</button>
    </form>
</div>
