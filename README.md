# Personal Auth (PHP 8.1+, MySQL 8+)

A complete server-rendered authentication project with registration, mandatory TOTP enrollment, TOTP login, roles, CSRF protection, rate limiting, password reset, and secure sessions.

## macOS Apple Silicon setup

You can install everything through Homebrew (recommended), or download the official installers individually:

- [Homebrew for macOS](https://brew.sh/) — package manager used by the recommended setup commands below. Its installer supports Apple Silicon Macs.
- [PHP for macOS](https://www.php.net/manual/en/install.macosx.packages.php) — PHP's official guide recommends Homebrew; the [Homebrew PHP formula](https://formulae.brew.sh/formula/php) installs PHP and its PDO MySQL extension.
- [MySQL Community Server downloads](https://dev.mysql.com/downloads/mysql/) — for a MacBook Air M2, select macOS and the **ARM 64-bit** package if using the direct installer. The [Homebrew MySQL formula](https://formulae.brew.sh/formula/mysql) is simpler.
- [Composer download and installation instructions](https://getcomposer.org/download/) — Composer manages this project's TOTP library. The [Homebrew Composer formula](https://formulae.brew.sh/formula/composer) is the easiest install.

Install Homebrew first if needed, then run these commands in Terminal:

```sh
brew update
brew install php mysql composer
brew services start mysql
php -v
mysql --version
composer --version
```

From this project directory install the pinned TOTP package and create the DB/user:

```sh
composer install
mysql -u root -p < database/schema.sql
mysql -u root -p
```

In MySQL, set a database password (choose your own strong value):

```sql
CREATE USER IF NOT EXISTS 'personal_auth'@'127.0.0.1' IDENTIFIED BY 'choose-a-strong-db-password';
GRANT ALL PRIVILEGES ON personal_auth.* TO 'personal_auth'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

## Environment configuration

Generate independent random secrets and export them in the same terminal before running the server:

```sh
export APP_ENV=local
export APP_URL=http://localhost:8000
export APP_KEY="$(php -r 'echo bin2hex(random_bytes(32));')"
export PASSWORD_PEPPER="$(php -r 'echo bin2hex(random_bytes(32));')"
export DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=personal_auth DB_USER=personal_auth
export DB_PASSWORD='choose-a-strong-db-password'
```

`.env.example` documents the variables, but PHP does not load it automatically. Set secrets in your shell/server configuration; never put the pepper or encryption key in public files, HTML, JavaScript, or Git. PHP `password_hash()` generates a random salt and embeds the salt and algorithm parameters in the resulting hash; do not create a separate salt. The pepper is appended server-side before hashing and verification. Losing/changing the pepper prevents password verification. `APP_KEY` encrypts TOTP seeds using AES-256-GCM; losing/changing it makes existing seeds unreadable.

## Start and use

```sh
php -S 127.0.0.1:8000 -t public
```

Open http://localhost:8000. Register; the next screen gives the secret and provisioning URI to add as a time-based account in Google Authenticator, Microsoft Authenticator, Authy, or another standard TOTP app. Confirm its six-digit code. Later logins require password then current six-digit TOTP code. Sessions become fully authenticated only after TOTP succeeds.

A new registration is always `USER`. To grant an administrator locally, connect to MySQL and promote that account deliberately:

```sql
UPDATE users SET role='ADMIN' WHERE username='your_username';
```

Sign out and sign back in; admins go to `/admin/dashboard.php`. A USER visiting that route receives HTTP 403. The role is checked on every server request.

Logout uses a CSRF-protected POST form and destroys the session. Opening a protected page afterward redirects to sign-in.

## Password reset in local development

Submit the account email at **Forgot password**. The same generic success response is shown whether or not that email exists. For `APP_ENV=local`, the actual link is appended to `var/reset-links.log`, outside the public document root. Read that file locally, open the link before its 30-minute expiration, and choose a new password. The token is random, only its SHA-256 hash is stored in MySQL, and successful use marks all outstanding tokens for that account used. Remove the private log after testing. In non-local deployments this project intentionally does not claim email delivery; configure a trusted mail transport before offering resets to users.

## Security notes

- PDO native prepared statements, unique username/email constraints, generic sign-in errors, IP+identifier hashed rate-limit buckets (8 failures / 15 minutes), and password hashing with a server-only pepper.
- CSRF tokens on every state-changing form; escaped output; strict session mode, regenerated session IDs, HttpOnly/SameSite cookies, CSP, and role checks on the server.
- TOTP seeds are encrypted at rest with AES-256-GCM under `APP_KEY`; codes are verified server-side with a one-step clock tolerance.
- Use HTTPS in production. Set the application behind a correctly configured TLS web server; ensure `HTTPS` is set so cookies receive Secure. Never expose `var/`, `config/`, `includes/`, or `database/` as document root; only `public/` is served. Keep PHP/MySQL patched, backups protected, secrets backed up separately, and configure real email delivery before production password resets.
- Set initial admin role only through a trusted database operator. Never allow public self-registration to choose a role.

## Project structure

```text
personal-auth/
├── public/assets/{css/style.css,js/script.js}
├── config/{database.php,security.php}
├── database/schema.sql
├── includes/{2fa.php,auth.php,bootstrap.php,csrf.php,functions.php}
├── public/{index.php,register.php,setup-2fa.php,login.php,verify-2fa.php,logout.php,dashboard.php,forgot-password.php,reset-password.php,admin/dashboard.php}
├── var/ (private local reset-link log)
├── composer.json
├── .env.example
└── .gitignore
```

## Troubleshooting

- **PDO MySQL missing:** `php -m | grep -E 'PDO|pdo_mysql'`; install/reinstall Homebrew PHP and ensure its `pdo_mysql` extension is loaded.
- **Connection refused:** `brew services start mysql`; verify `DB_HOST`, `DB_PORT`, credentials, and MySQL grants.
- **APP_KEY error:** export exactly 64 hexadecimal characters. Keep it stable across restarts.
- **Missing vendor autoload:** run `composer install` in the project root.
- **No reset link:** local mode logs only for an existing account. Check `var/reset-links.log`; it is deliberately not served by PHP's `-t public` root.
- **TOTP rejected:** ensure the phone's time is automatic/current and enter the latest 6-digit code.

## Verification status

File paths, endpoint targets, schema names, session keys, roles, and redirects were checked as a connected project. This execution environment had no `php`, `composer`, or `mysql` binaries, so runtime installation, database integration, and the requested live end-to-end scenarios could not be executed here. Run the setup steps above on the Mac before deployment; the project is not represented as runtime-tested in this environment.

## End-to-end acceptance checklist

After completing installation, walk through these checks in a browser:

1. Register an account with a new username. Confirm a row appears in `users` with `totp_enabled=1` after setup and a non-readable `password_hash`.
2. Register again with the same username (and separately the same email). Each request is rejected with a duplicate-account message.
3. Sign in with the right username and a wrong password. The generic sign-in error is shown; no authenticated dashboard session is created.
4. Sign in with the correct password. The browser reaches `/verify-2fa.php`, not the dashboard.
5. Enter a wrong six-digit code. Access is rejected. Repeated failures are rate-limited.
6. Enter the current authenticator code. A USER reaches `/dashboard.php`.
7. While signed in as USER, open `/admin/dashboard.php` directly. The server responds with HTTP 403.
8. Promote a test account with the SQL above, sign in again, and verify an ADMIN reaches `/admin/dashboard.php`.
9. Sign out, then request `/dashboard.php` and `/admin/dashboard.php` directly. Both require authentication.
10. Try `" OR 1=1 --` as a username or email with any password. It is treated as ordinary input and sign-in fails.
11. Submit a state-changing form without its `_csrf` field (for example using browser developer tools to remove it). The server rejects the request with HTTP 419.
12. Request a reset for a known account, open the private local reset link, change the password, then confirm the old password fails and the new password reaches 2FA. Reusing the link must fail.

The checklist describes expected behavior. It was not executed here because PHP, Composer, and MySQL are not installed in the provided environment.
