<?php

// Public routes do not require authentication
$publicRoutes = ['login', 'signup'];

if (!in_array($page, $publicRoutes)) {
    Auth::requireLogin();
}

switch ($page) {
    case 'login':
        require __DIR__ . '/../views/auth/login.php';
        break;

    case 'signup':
        // signup view is named `signin.php` on disk (register form)
        require __DIR__ . '/../views/auth/signin.php';
        break;

    case 'dashboard':
        require __DIR__ . '/../views/dashboard.php';
        break;

    case 'clients':
        require __DIR__ . '/../views/clients.php';
        break;

    case 'invoice':
        require __DIR__ . '/../views/invoice.php';
        break;

    case 'manage-invoice':
        require __DIR__ . '/../views/manage_invoice.php';
        break;

    case 'reports':
        require __DIR__ . '/../views/reports.php';
        break;

    case 'profile':
        require __DIR__ . '/../views/profile.php';
        break;
    case 'create':
    case 'create-hub':
        require __DIR__ . '/../views/create_hub.php';
        break;

    case 'create-document':
        require __DIR__ . '/../views/create_document.php';
        break;

    default:
        http_response_code(404);
        echo "Page not found";
}
