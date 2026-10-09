<?php

require_once __DIR__.'/../includes/bootstrap.php';

$u = require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare(
        'SELECT password_hash
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([(int)$u['id']]);
    $user = $stmt->fetch();

    if (!$user || !password_matches($password, $user['password_hash'])) {
        log_activity(
            'ACCOUNT_DELETE_FAILED',
            (int)$u['id'],
            'Incorrect password supplied for account deletion'
        );

        flash(
            'error',
            'The password was incorrect. Your account was not deleted.'
        );

        redirect('/delete-account.php');
    }

    log_activity(
        'ACCOUNT_DELETED',
        (int)$u['id'],
        'User deleted their account'
    );

    $delete = db()->prepare(
        'DELETE FROM users
         WHERE id = ?'
    );

    $delete->execute([(int)$u['id']]);

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

    flash(
        'success',
        'Your account has been permanently deleted.'
    );

    redirect('/login.php');
}

render_start('Delete account');
?>

<p class="lede">
    This action permanently deletes your account and cannot be undone.
</p>

<form method="post">
    <?= csrf_field() ?>

    <label>
        Enter your password to confirm
        <span class="pw">
            <input
                type="password"
                name="password"
                autocomplete="current-password"
                required
            >
            <button type="button" class="toggle">Show</button>
        </span>
    </label>

    <button
        class="button"
        type="submit"
        onclick="return confirm('Are you sure you want to permanently delete your account? This cannot be undone.');"
    >
        Permanently delete my account
    </button>
</form>

<div class="links">
    <a href="/dashboard.php">Cancel</a>
</div>

<?php render_end();
