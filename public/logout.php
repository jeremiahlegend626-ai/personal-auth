<?php

require_once __DIR__.'/../includes/bootstrap.php';

require_post();
verify_csrf();

$user = current_user();

if ($user) {
    log_activity(
        'LOGOUT',
        (int)$user['id'],
        'User signed out'
    );
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $p['path'],
            'domain' => $p['domain'],
            'secure' => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => 'Lax'
        ]
    );
}

session_destroy();

session_start();
session_regenerate_id(true);

flash('success', 'You have been signed out.');
redirect('/login.php');
