<?php
declare(strict_types=1);

function mailer(): PHPMailer\PHPMailer\PHPMailer
{
    static $mailer = null;

    if ($mailer instanceof PHPMailer\PHPMailer\PHPMailer) {
        return $mailer;
    }

    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);

    $mailer->isSMTP();
    $mailer->Host = getenv('MAIL_HOST') ?: '';
    $mailer->Port = (int)(getenv('MAIL_PORT') ?: 587);
    $mailer->SMTPAuth = true;
    $mailer->Username = getenv('MAIL_USERNAME') ?: '';
    $mailer->Password = getenv('MAIL_PASSWORD') ?: '';

    $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

    $mailer->setFrom(
        getenv('MAIL_FROM_ADDRESS') ?: '',
        getenv('MAIL_FROM_NAME') ?: 'Personal Auth'
    );

    $mailer->isHTML(true);
    $mailer->CharSet = 'UTF-8';

    return $mailer;
}
