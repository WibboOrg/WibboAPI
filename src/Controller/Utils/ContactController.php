<?php
namespace App\Controller\Utils;

use App\Controller\DefaultController;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Helper\Utils;
use Exception;

class ContactController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['username', 'email', 'sujet', 'message', 'recaptchaToken']);

        $username = $data->username;
        $filterUsername = preg_replace("/[^a-z\d\-=\?!@:\.]/i", "", $username);
        if ($filterUsername !== $username)
            $username = null;

        $sujet = $data->sujet;
        if (strlen($sujet) < 3 || strlen($sujet) > 100) {
            throw new Exception('forum.empty-sujet', 400);
        }

        $message = $data->message;
        if (strlen($message) < 6 || strlen($message) > 1000) {
            throw new Exception('forum.empty-message', 400);
        }

        $result = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . getenv('RECAPTCHA') . "&response=" . $data->recaptchaToken . "&remoteip=" . Utils::getUserIP());

        $etat = json_decode($result);
        if ($etat->success != "1") {
            throw new Exception('captcha', 400);
        }

        $email = strtolower($data->email);
        $email_check = preg_match("/^[a-z0-9_\.-]+@([a-z0-9]+([\-]+[a-z0-9]+)*\.)+[a-z]{2,7}$/i", $email);
        if (strlen($email) < 6 || $email_check !== 1 || Utils::junkMail($email)) {
            throw new Exception('mail.invalid', 400);
        }

        if($username != null) {
            $message .= "<br><br>-$username";
        }

        if (!$this->mail->sendMail($email, $message, "[SUPPORT] $sujet", false, true)) {
            throw new Exception('error', 400);
        }

        return $this->jsonResponse($response, []);
    }
}
