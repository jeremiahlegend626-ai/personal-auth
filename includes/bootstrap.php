<?php
declare(strict_types=1);
require_once __DIR__.'/../config/security.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/functions.php'; require_once __DIR__.'/csrf.php';
require_once __DIR__.'/activity.php'; require_once __DIR__.'/auth.php'; require_once __DIR__.'/2fa.php';
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/email_otp.php';
require_once __DIR__.'/../config/mail.php';
