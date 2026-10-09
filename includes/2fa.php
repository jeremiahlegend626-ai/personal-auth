<?php
declare(strict_types=1);
use PragmaRX\Google2FA\Google2FA;
function totp(): Google2FA { static $g=null; return $g ??= new Google2FA(); }
function app_key(): string { $v=getenv('APP_KEY') ?: ''; if(!preg_match('/^[a-f0-9]{64}$/i',$v)) throw new RuntimeException('APP_KEY must be 64 hexadecimal characters.'); return hex2bin($v); }
function encrypt_secret(string $secret): string { $iv=random_bytes(12); $tag=''; $cipher=openssl_encrypt($secret,'aes-256-gcm',app_key(),OPENSSL_RAW_DATA,$iv,$tag); if($cipher===false) throw new RuntimeException('TOTP encryption failed.'); return base64_encode($iv.$tag.$cipher); }
function decrypt_secret(string $value): string { $raw=base64_decode($value,true); if($raw===false||strlen($raw)<29) throw new RuntimeException('Invalid encrypted TOTP secret.'); $plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',app_key(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16)); if($plain===false) throw new RuntimeException('Unable to decrypt TOTP secret.'); return $plain; }
