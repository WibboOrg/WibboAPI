<?php
namespace App\Controller\Utils;

use App\Controller\DefaultController;
use Exception;

class ContactController extends DefaultController
{
    public function post($request, $response, $args)
    {
        $input = $request->getParsedBody();
        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['email', 'sujet', 'message', 'recaptchaToken']);

        $sujet = $data->sujet;
        if (strlen($sujet) < 3 || strlen($sujet) > 100) {
            throw new Exception('forum.empty-sujet', 400);
        }

        $message = $data->message;
        if (strlen($message) < 6 || strlen($message) > 1000) {
            throw new Exception('forum.empty-message', 400);
        }

        $result = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . getenv('RECAPTCHA') . "&response=" . $data->recaptchaToken . "&remoteip=" . getUserIP());

        $etat = json_decode($result);
        if ($etat->success != "1") {
            throw new Exception('captcha', 400);
        }

        $email = strtolower($data->email);
        $email_check = preg_match("/^[a-z0-9_\.-]+@([a-z0-9]+([\-]+[a-z0-9]+)*\.)+[a-z]{2,7}$/i", $email);
        if (strlen($email) < 6 || $email_check !== 1 || $this->junkMail($email)) {
            throw new Exception('mail.invalid', 400);
        }

        if (!$this->mail->sendMail($email, $message, "[WIBBO SUPPORT] ${$sujet}", false, true)) {
            throw new Exception('error', 400);
        }

        return $this->jsonResponse($response, null);
    }

    public function junkMail($mail)
    {
        $domains = array('@wibbo.org', 'pjjkp.com', 'ephemail.com', 'ephemail.org', 'ephemail.net', 'jetable.org', 'jetable.net', 'jetable.com', 'haltospam.com', 'tempinbox.com', 'brefemail.com', '0-mail.com', 'link2mail.net', 'mailexpire.com', 'spambox.info', 'mytrashmail.com', 'mailinator.com', 'dontreg.com', 'maileater.com', 'brefemail.com', '0-mail.com', 'brefemail.com', 'ephemail.net', 'guerrillamail.com', 'guerrillamail.info', 'haltospam.com', 'iximail.com', 'jetable.net', 'jetable.org', 'kasmail.com', 'klassmaster.com', 'kleemail.com', 'link2mail.net', 'mailin8r.com', 'mailinator.com', 'mailinator.net', 'mailinator2.com', 'myamail.com', 'nyms.net', 'shortmail.net', 'sogetthis.com', 'spambox.us', 'spamday.com', 'Spamfr.com', 'spamgourmet.com', 'spammotel.com', 'tempinbox.com', 'yopmail.fr', 'guerrillamail.org', 'temporaryinbox.com', 'spamcorptastic.com', 'filzmail.com', 'lifebyfood.com', 'tempemail.net', 'spamfree24.org', 'spamfree24.com', 'spamfree24.net', 'spamfree24.de', 'spamfree24.eu', 'spamfree24.info', 'spamherelots.com', 'thisisnotmyrealemail.com', 'slopsbox.com', 'trashmail.net', 'myamail.com', 'tyldd.com', 'safetymail.info', 'brefmail.com', 'bofthew.com', 'trash-mail.com', 'wimsg.com', 'emailo.pro', 'boximail.com');

        list($user, $domain) = explode('@', $mail);

        return in_array($domain, $domains);
    }
}
