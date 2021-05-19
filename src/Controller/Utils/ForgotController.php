<?php
namespace App\Controller\Utils;

use App\Controller\DefaultController;
use App\Models\Forgot;
use App\Models\User;
use Exception;

class ForgotController extends DefaultController
{
    private $_timeExpire = 48 * 60 * 60;

    public function verifForgot($request, $response, $args)
    {
        if (empty($args['code'])) {
            throw new Exception('mail.code-invalid', 400);
        }

        $forgot = Forgot::where('pass', $args['code'])->first();
        if (!$forgot) {
            throw new Exception('mail.code-invalid', 400);
        }

        $forgotExpire = $forgot->expire + $this->_timeExpire;

        if ($forgotExpire < time()) {
            Forgot::where('pass', $args['code'])->delete();
            throw new Exception('mail.expirer', 400);
        }

        $mdp = $this->createRandomPassword();
        $newpassword = md5($mdp);

        $htmlText = "Salut " . $forgot->users . ", votre nouveau mot de passe est: " . $mdp . " s'il vous plaît changer le après VOTRE connection sur le site.";

        if ($this->mail->sendMail($forgot->email, $htmlText, 'Ton nouveau mot de passe sur Wibbo!')) {
            User::where('username', $forgot->users)->update([
                'password' => $newpassword,
            ]);

            Forgot::where('pass', $args['code'])->delete();

        } else {
            throw new Exception('error', 400);
        }

        return $this->jsonResponse($response, null);
    }

    public function postForgot($request, $response)
    {
        $input = $request->getParsedBody();
        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['username', 'email']);

        $email_check = preg_match("/^[a-z0-9_\.-]+@([a-z0-9]+([\-]+[a-z0-9]+)*\.)+[a-z]{2,7}$/i", $data->email);
        if (strlen($data->email) < 6 || $email_check !== 1) {
            throw new Exception('mail.invalid', 400);
        }

        $forgot = Forgot::where('users', $data->username)->where('email', $data->email)->first();
        if ($forgot) {
            $forgot_expire = $forgot->expire + $this->_timeExpire;
            if ($forgot_expire > time()) {
                throw new Exception('mail.in-progress', 400);
            }
            Forgot::where('users', $data->username)->where('email', $data->email)->delete();
        }

        $user = User::where('username', $data->username)->where('mail', $data->email)->where('mail_valide', '1')->first();
        if (!$user) {
            throw new Exception('mail.deny', 400);
        }

        $code = md5($this->GeraHash(10));

        $username = $data->username;
        $email = $data->email;
        $url = "https://wibbo.org/forgot/" . $code;
        $sujet = "Mot de passe oublier";

        $str = <<<html
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Ubuntu:regular,bold|Ubuntu+Condensed:regular">
    <style type="text/css">
    #body { color: #000000; font-family: 'Ubuntu', 'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif; background-color: #ffffff; -webkit-font-smoothing: antialiased; margin: 0; padding: 0; -ms-text-size-adjust: none; -webkit-text-size-adjust: none; width: 100%; }
    p { font-size: 16px; line-height: 1.4; padding: 0; margin: 0 0 16px 0; }
    .full { width: 100%; }
    table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    .button { text-decoration: none; background-color: #00813e; -moz-border-radius: 5px; -webkit-border-radius: 5px; border-radius: 5px; display: inline-block; font-family: 'Ubuntu Condensed', 'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif; font-size: 18px; padding: 8px 24px; }
    .container { border-collapse: collapse; padding: 0 10px 0 10px; }
    .header { border-collapse: collapse; padding: 10px 0 0 0; }
    .content { border-collapse: collapse; padding: 32px 0 24px 0; }
    .footer { border-collapse: collapse; color: #818a91; border-top: 1px solid #aaaaaa; font-size: 10px; line-height: 1.4; padding: 10px; }
    .title { font-family: 'Ubuntu Condensed', 'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif; font-size: 24px; font-weight: normal; line-height: 1; margin: 0; padding: 0 0 24px 0; }
    </style>
</head>
<body id="body">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
        <tr>
            <td class="container">
                <table align="center" border="0" cellpadding="0" cellspacing="0" class="full">
                    <tr>
                        <td align="left" class="header"><img src="cid:wibbologo" alt="Wibbo" height="33" width="134" style="-ms-interpolation-mode: bicubic; image-rendering: pixelated;"></td>
                    </tr>
                </table>
                <table align="center" border="0" cellpadding="0" cellspacing="0" class="full">
                    <tr>
                        <td align="left" class="content"><h1 class="title">Salut $username!</h1>
                        <p>On nous a dit que tu as besoin de réinitialiser ton mot de passe pour le compte Wibbo connecté à $email.</p>
                        <p>Le lien ci-dessous expire dans 48 heures, alors fais-le vite!</p>
                        <p><a href="$url" class="button" style="color: #ffffff;">Clique ici pour réinitialiser ton mot de passe en toute sécurité</a></p>
                        <p>Tu n'as pas demandé ce changement? S'il s'agit d'une erreur, ignore simplement cet e-mail et ton mot de passe actuel restera valable.</p>
                        <p style="margin:0;">À bientôt,<br>- L'équipe Wibbo.org</p>
                        </td>
                    </tr>
                </table>
                <table align="center" border="0" cellpadding="0" cellspacing="0" class="full">
                    <tr>
                        <td align="center" class="footer">
                        © 2011-2021 Wibbo Hôtel.<br>
                        Wibbo est un projet indépendant, à but non lucratif.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
html;

        if ($this->mail->sendMail($email, $str, $sujet, true)) {
            Forgot::insert([
                'pass' => $code,
                'users' => $data->username,
                'email' => $data->email,
                'expire' => time(),
            ]);
        } else {
            throw new Exception('error', 400);
        }

        return $this->jsonResponse($response, null);
    }

    private function createRandomPassword()
    {
        $chars = "abcdefghijkmnopqrstuvwxyz023456789ABCDEFGHIJKMNOPQRSTUVWXYZ";
        srand((double) microtime() * 1000000);
        $i = 0;
        $pass = '';

        while ($i <= 7) {
            $num = rand() % 33;
            $tmp = substr($chars, $num, 1);
            $pass = $pass . $tmp;
            $i++;
        }

        return $pass;
    }

    private function GeraHash($qtd)
    {
        $Caracteres = 'abcdefghijklmopqrstuvxwyzABCDEFGHIJKLMOPQRSTUVXWYZ0123456789';
        $QuantidadeCaracteres = strlen($Caracteres);
        $QuantidadeCaracteres--;
        $Hash = null;
        for ($x = 1; $x <= $qtd; $x++) {
            $Posicao = rand(0, $QuantidadeCaracteres);
            $Hash .= substr($Caracteres, $Posicao, 1);
        }
        return $Hash;
    }
}
