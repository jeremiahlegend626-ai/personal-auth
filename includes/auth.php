<?php
declare(strict_types=1);
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_auth(): array { $u=current_user(); if(!$u) redirect('/login.php'); return $u; }
function require_role(string $role): array { $u=require_auth(); if($u['role']!==$role){http_response_code(403); render_start('Access denied'); echo '<p>Your account is not allowed to open this page.</p><a class="button" href="/dashboard.php">Return to dashboard</a>'; render_end(); exit;} return $u; }
function complete_login(array $u): void {
    session_regenerate_id(true);
    $_SESSION['user']=[
        'id'=>(int)$u['id'],
        'name'=>$u['full_name'],
        'username'=>$u['username'],
        'role'=>$u['role']
    ];
    unset($_SESSION['pending_2fa']);

    log_activity(
        'LOGIN_SUCCESS',
        (int)$u['id'],
        'User signed in successfully'
    );
}
