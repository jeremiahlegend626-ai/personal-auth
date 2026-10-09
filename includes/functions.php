<?php
declare(strict_types=1);
function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function redirect(string $path): never { header('Location: '.$path,true,303); exit; }
function flash(string $type,string $message): void { $_SESSION['flash']=['type'=>$type,'message'=>$message]; }
function take_flash(): ?array { $f=$_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function render_start(string $title): void { $f=take_flash(); ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> · Personal Auth</title><link rel="stylesheet" href="/assets/css/style.css"><script defer src="/assets/js/script.js"></script></head><body><header class="top"><a href="/" class="brand">Northstar<span> / secure account</span></a><nav><a href="/login.php">Sign in</a><a href="/register.php">Create account</a></nav></header><main class="wrap"><section class="card"><div class="eyebrow">PERSONAL WEBSITE</div><h1><?=e($title)?></h1><?php if($f): ?><div class="alert <?=e($f['type'])?>"><?=e($f['message'])?></div><?php endif; ?><?php }
function render_end(): void { ?></section><footer>Protected with PHP, MySQL, and time-based one-time passwords.</footer></main></body></html><?php }
function password_valid(string $password): bool { return strlen($password)>=12 && strlen($password)<=200 && preg_match('/[a-z]/',$password) && preg_match('/[A-Z]/',$password) && preg_match('/[0-9]/',$password) && preg_match('/[^A-Za-z0-9]/',$password); }
function password_digest(string $password): string { $pepper=getenv('PASSWORD_PEPPER'); if (!$pepper || str_starts_with($pepper,'REPLACE_')) throw new RuntimeException('PASSWORD_PEPPER is not configured.'); return password_hash($password.$pepper,PASSWORD_DEFAULT); }
function password_matches(string $password,string $hash): bool { $pepper=getenv('PASSWORD_PEPPER'); if (!$pepper || str_starts_with($pepper,'REPLACE_')) throw new RuntimeException('PASSWORD_PEPPER is not configured.'); return password_verify($password.$pepper,$hash); }
function require_post(): void { if($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit('Method not allowed'); } }
function attempt_key(string $subject): string { return hash('sha256',strtolower(trim($subject)).'|'.($_SERVER['REMOTE_ADDR'] ?? 'unknown')); }
function rate_limited(string $subject,int $limit=8,int $minutes=15): bool { $q=db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE subject_hash=? AND attempted_at > (UTC_TIMESTAMP() - INTERVAL ? MINUTE) AND succeeded=0'); $q->execute([attempt_key($subject),$minutes]); return (int)$q->fetchColumn()>=$limit; }
function log_attempt(string $subject,bool $success): void { $q=db()->prepare('INSERT INTO login_attempts(subject_hash,attempted_at,succeeded) VALUES(?,UTC_TIMESTAMP(),?)'); $q->execute([attempt_key($subject),$success?1:0]); }
