<?php
declare(strict_types=1);
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode','1'); ini_set('session.use_only_cookies','1'); ini_set('session.cookie_httponly','1');
session_name('personal_auth');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('X-Content-Type-Options: nosniff'); header('X-Frame-Options: DENY'); header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
