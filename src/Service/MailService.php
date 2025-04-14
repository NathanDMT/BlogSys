<?php

namespace App\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    public function sendResetPasswordEmail(string $to, string $subject, string $token): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Config SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'demoineretnathan@gmail.com';
            $mail->Password = 'uxik gxsh qylv utmf';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            // Expéditeur
            $mail->setFrom('noreply@monsite.com', 'BlogSys');
            $mail->addAddress($to);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = "Bonjour,<br><br>
                Cliquez sur le lien suivant pour réinitialiser votre mot de passe :<br>
                <a href='https://127.0.0.1:8000/reset-password/$token'>Réinitialiser mon mot de passe</a>";

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log ou debug si besoin : $mail->ErrorInfo
            return false;
        }
    }
}
