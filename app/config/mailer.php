<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/../../vendor/autoload.php";

function mailer() {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'noreply@ht.ianconroy.co.uk';
    $mail->Password   = 'Dav1d1405!';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('noreply@ht.ianconroy.co.uk', 'Health Tracker');
    $mail->isHTML(true);

    return $mail;
}
