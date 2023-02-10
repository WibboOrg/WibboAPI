<?php 
namespace App\Service;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    public function sendMail(string $email, string $htmlText, string $sujet, bool $logo = false, bool $server = false): bool
    {
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = '';
        $mail->Host = getenv('MAIL_HOST');
        $mail->Port = getenv('MAIL_PORT');
        $mail->Username = getenv('MAIL_USERNAME');
        $mail->Password = getenv('MAIL_PASSWORD');
        $mail->From = getenv('MAIL_USERNAME');
        $mail->FromName = "Wibbo Hotel";
        if($server)
        {
            $mail->AddAddress(getenv('MAIL_USERNAME'));
            $mail->AddReplyTo($email, "Support");
        }
        else 
        {
            $mail->AddAddress($email);
            $mail->AddReplyTo(getenv('MAIL_USERNAME'), "Support");
        }
        $mail->IsHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $sujet;
        $mail->Body = $htmlText;
        if ($logo) {
            $mail->addEmbeddedImage('illustration.png', 'illustration');
            $mail->Encoding = "base64";
        }

        return $mail->Send();
    }
}