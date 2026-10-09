<?php

require_once __DIR__.'/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $identifier = trim($_POST['identifier'] ?? '');
    $pw = $_POST['password'] ?? '';

    if (
        $identifier === '' ||
        strlen($identifier) > 254 ||
        rate_limited($identifier)
    ) {
        flash(
            'error',
            'Sign-in failed. Check your credentials or wait before trying again.'
        );
    } else {
        $q = db()->prepare(
            'SELECT *
             FROM users
             WHERE username = ? OR email = ?
             LIMIT 1'
        );

        $q->execute([
            $identifier,
            strtolower($identifier)
        ]);

        $u = $q->fetch();

        $ok = $u && password_matches(
            $pw,
            $u['password_hash']
        );

        log_attempt(
            $identifier,
            (bool)$ok
        );

        if (!$ok) {
            log_activity(
                'LOGIN_FAILED',
                $u ? (int)$u['id'] : null,
                'Failed sign-in attempt'
            );

            flash(
                'error',
                'Sign-in failed. Check your credentials or wait before trying again.'
            );
        } else {
            session_regenerate_id(true);

            $_SESSION['pending_2fa'] = (int)$u['id'];

            try {
                create_email_otp(
                    (int)$u['id'],
                    $u['email'],
                    $u['full_name']
                );

                log_activity(
                    'EMAIL_2FA_SENT',
                    (int)$u['id'],
                    'Email verification code sent'
                );

                redirect('/verify-email-2fa.php');
            } catch (Throwable $e) {
                error_log(
                    'Email 2FA error: '.$e->getMessage()
                );

                unset($_SESSION['pending_2fa']);

                flash(
                    'error',
                    'We could not send your verification code. Please try again later.'
                );
            }
        }
    }
}

render_start('Welcome back');
?>

<p class="lede">
    Use your username or email and password. We will then send a verification code to your email.
</p>

<form method="post" data-validate>
    <?= csrf_field() ?>

    <label>
        Username or email
        <input
            name="identifier"
            autocomplete="username"
            required
        >
    </label>

    <label>
        Password
        <span class="pw">
            <input
                type="password"
                name="password"
                autocomplete="current-password"
                required
            >
            <button
                type="button"
                class="toggle"
            >
                Show
            </button>
        </span>
    </label>

    <button class="button">
        Continue
    </button>
</form>

<div class="links">
    <a href="/forgot-password.php">Forgot password?</a>
    <a href="/register.php">Create account</a>
</div>

<?php render_end();
