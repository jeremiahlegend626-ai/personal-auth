<?php

require_once __DIR__.'/../includes/bootstrap.php';

$u = require_auth();

if ($u['role'] === 'ADMIN') {
    redirect('/admin/dashboard.php');
}

render_start('Your dashboard');
?>

<p class="lede">
    Signed in as <?= e($u['name']) ?>
    <span class="badge">USER</span>
</p>

<p>
    Your account is protected by two-factor authentication.
</p>

<form method="post" action="/logout.php">
    <?= csrf_field() ?>
    <button class="button secondary">
        Sign out
    </button>
</form>

<hr>

<h2>Account</h2>

<p>
    You can permanently delete your account here.
</p>

<a
    class="button secondary"
    href="/delete-account.php"
>
    Delete my account
</a>

<?php render_end();
