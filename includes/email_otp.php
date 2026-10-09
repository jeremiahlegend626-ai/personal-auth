<?php
declare(strict_types=1);

function create_email_otp(int $userId, string $email, string $name): void
{
    $code = (string) random_int(100000, 999999);
    $hash = password_hash($code, PASSWORD_DEFAULT);

    $db = db();

    // Remove previous unused codes for this user.
    $stmt = $db->prepare(
        'DELETE FROM email_otp_codes
         WHERE user_id = ? AND used_at IS NULL'
    );
    $stmt->execute([$userId]);

    // Code expires after 10 minutes.
    $stmt = $db->prepare(
        'INSERT INTO email_otp_codes
         (user_id, code_hash, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))'
    );
    $stmt->execute([$userId, $hash]);

    $mail = mailer();

    $mail->addAddress($email, $name);
    $mail->Subject = 'Your Personal Auth verification code';

    $mail->Body = '
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:auto">
            <h2>Personal Auth</h2>
            <p>Hello '.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').',</p>
            <p>Your verification code is:</p>
            <p style="font-size:32px;font-weight:bold;letter-spacing:8px">
                '.$code.'
            </p>
            <p>This code expires in <strong>10 minutes</strong>.</p>
            <p>If you did not request this code, you can safely ignore this email.</p>
        </div>
    ';

    $mail->AltBody =
        "Your Personal Auth verification code is {$code}. "
        ."This code expires in 10 minutes.";

    $mail->send();
}

function verify_email_otp(int $userId, string $code): bool
{
    $db = db();

    $stmt = $db->prepare(
        'SELECT id, code_hash, expires_at, attempts
         FROM email_otp_codes
         WHERE user_id = ?
           AND used_at IS NULL
         ORDER BY id DESC
         LIMIT 1'
    );

    $stmt->execute([$userId]);
    $otp = $stmt->fetch();

    if (!$otp) {
        return false;
    }

    if (strtotime($otp['expires_at']) < time()) {
        return false;
    }

    if ((int)$otp['attempts'] >= 5) {
        return false;
    }

    $update = $db->prepare(
        'UPDATE email_otp_codes
         SET attempts = attempts + 1
         WHERE id = ?'
    );

    $update->execute([(int)$otp['id']]);

    if (!preg_match('/^\d{6}$/', $code)) {
        return false;
    }

    if (!password_verify($code, $otp['code_hash'])) {
        return false;
    }

    $markUsed = $db->prepare(
        'UPDATE email_otp_codes
         SET used_at = NOW()
         WHERE id = ? AND used_at IS NULL'
    );

    $markUsed->execute([(int)$otp['id']]);

    return $markUsed->rowCount() === 1;
}
