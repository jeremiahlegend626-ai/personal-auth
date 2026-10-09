<?php

require_once __DIR__.'/../includes/bootstrap.php';

$id = $_SESSION['pending_2fa'] ?? null;

if (!$id) {
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $code = preg_replace('/\D/', '', $_POST['code'] ?? '');

    if (rate_limited('email-2fa:'.$id, 5, 10)) {
        flash(
            'error',
            'Too many verification attempts. Please sign in again later.'
        );

        redirect('/login.php');
    }

    $q = db()->prepare(
        'SELECT *
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

    $q->execute([(int)$id]);
    $u = $q->fetch();

    if (!$u) {
        unset($_SESSION['pending_2fa']);
        redirect('/login.php');
    }

    if (verify_email_otp((int)$u['id'], $code)) {
        complete_login($u);

        redirect(
            $u['role'] === 'ADMIN'
                ? '/admin/dashboard.php'
                : '/dashboard.php'
        );
    }

    flash(
        'error',
        'The code was not accepted. Check the code and try again.'
    );
}

render_start('Verify your email');
?>

<p class="lede">
    Enter the six-digit code we sent to your email address.
</p>

<form method="post">
    <?= csrf_field() ?>

    <label>
        Email verification code
        <input
            name="code"
            inputmode="numeric"
            pattern="[0-9]{6}"
            maxlength="6"
            autocomplete="one-time-code"
            required
            autofocus
        >
    </label>

    <button class="button">
        Verify and sign in
    </button>
</form>

<div class="links">
    <a href="/login.php">Return to login</a>
</div>

<?php render_end();
