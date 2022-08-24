<?php
namespace App\Controller\Settings;

use App\Controller\DefaultController;
use App\Models\MailConfirm;
use App\Models\User;
use App\Models\UserStats;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Helper\Utils;
use Exception;

class EmailController extends DefaultController
{
    private int $timeExpire = 48 * 60 * 60;

    public function getMail(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::select('mail')->where('id', $userId)->first();

        if(!$user) throw new Exception('disconnect', 401);

        $mail = MailConfirm::where('user_id', '=', $userId)->first();
        if ($mail) {
            $temps = $mail->temps + $this->timeExpire;
            if (time() > $temps) {
                MailConfirm::where('user_id', '=', $userId)->delete();
                
                throw new Exception('mail.expirer', 400);
            }
        }

        $message = [
            'check' => $mail
        ];

        return $this->jsonResponse($response, $message);
    }

    public function formMail(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['mail']);

        $user = User::select('username', 'mail')->where('id', $userId)->first();

        if(!$user) throw new Exception('disconnect', 401);

        $email = strtolower($data->mail);
        $emailCheck = preg_match("/^[a-z0-9_\.-]+@([a-z0-9]+([\-]+[a-z0-9]+)*\.)+[a-z]{2,7}$/i", $email);
        if (strlen($email) < 6 || $emailCheck !== 1 || Utils::junkMail($email))
            throw new Exception('mail.invalid', 400);
            
        $mails = MailConfirm::where('user_id', '=', $userId)->first();
        if ($mails)
            throw new Exception('mail.in-progress', 400);

        $mail = null;

        if (empty($user->mail)) {
            $userCheck = User::where('mail', $email)->select('id')->first();
            if ($userCheck)
                throw new Exception('mail.exist', 400);

            $code = md5(Utils::generateHash(10));
            $expire = time() + $this->timeExpire;
            $messageText = "Salut " . $user->username . ", va sur ce lien <a href=\"https://wibbo.org/settings/email/" . $code . "\">https://wibbo.org/settings/email/" . $code . "</a> pour valider ton email. Attention, la date limite pour utilisé ce code de validation est le " . date("d-m-Y à H:i:s", $expire);

            if (!$this->mail->sendMail($email, $messageText, 'Active ton compte Wibbo'))
                throw new Exception('error', 400);

            MailConfirm::insert([
                'user_id' => $userId,
                'codedevalidation' => $code,
                'email' => $email,
                'temps' => time(),
            ]);

            $mail = ['type' => 0, 'temps' => time(), 'email' => $email];
        }

        else {
            if ($email == $user->mail)
                throw new Exception('mail.idiot', 400);

            $userCheck = User::where('mail', '=', $email)->select('id')->first();
            if ($userCheck)
                throw new Exception('mail.exist', 400);

            $code = md5(Utils::generateHash(10));
            $expire = time() + 60 * 60 * 4;
            $messageText = "Salut " . $user->username . ", va sur ce lien <a href=\"https://wibbo.org/settings/email/" . $code . "\">https://wibbo.org/settings/email/" . $code . "</a> pour valider ton email. Attention ce code de validation est valide que jusqu'aux " . date("d-m-Y à H:i:s", $expire);

            if (!$this->mail->sendMail($user->mail, $messageText, 'Active ton compte Wibbo')) 
                throw new Exception('error', 400);
                
            MailConfirm::insert([
                'user_id' => $userId,
                'codedevalidation' => $code,
                'email' => $email,
                'temps' => time(),
                'type' => '1',
            ]);

            $mail = ['type' => 1, 'temps' => time(), 'email' => $email];
        }

        $message = [
            'check' => $mail
        ];

        return $this->jsonResponse($response, $message);
    }

    public function getCode(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $mails = MailConfirm::where('user_id', '=', $userId)->first();

        if (!$mails)
            throw new Exception('mail.expirer', 400);

        $temps = $mails->temps + $this->timeExpire;
        if (time() > $temps) {
            MailConfirm::where('user_id', '=', $userId)->delete();

            throw new Exception('mail.expirer', 400);
        }

        if ($args['code'] != $mails->codedevalidation)
            throw new Exception('mail.code-invalid', 400);

        $userCheck = User::where('mail', $mails->email)->select('id')->first();
        if ($userCheck) {
            MailConfirm::where('user_id', '=', $userId)->delete();

            throw new Exception('mail.exist', 400);
        }

        $user = User::select('mail', 'username', 'online')->where('id', $userId)->first();

        if(!$user) throw new Exception('disconnect', 401);

        $mail = null;

        if (empty($user->mail))
        {
            User::where('id', '=', $userId)->update(['mail' => $mails->email]);
            UserStats::where('id', '=', $userId)->increment('achievement_score', '200');

            if($user->online) Utils::sendMusCommand('addwinwin', $userId . chr(1) . '200');

            MailConfirm::where('user_id', '=', $userId)->delete();
        } 
        else if ($mails->type == '1') 
        {
            MailConfirm::where('user_id', '=', $userId)->delete();

            $code = md5(Utils::generateHash(10));
            $expire = time() + $this->timeExpire;
            $textHtml = "Salut " . $user->username . ", va sur ce lien <a href=\"https://wibbo.org/settings/email/" . $code . "\">https://wibbo.org/settings/email/" . $code . "</a> pour valider ton email. Attention ce code de validation est valide que jusqu'aux " . date("d-m-Y à H:i:s", $expire);

            if (!$this->mail->sendMail($mails->email, $textHtml, 'Active ton compte Wibbo'))
                throw new Exception('error', 400);

            MailConfirm::insert([
                'user_id' => $userId,
                'codedevalidation' => $code,
                'email' => $mails->email,
                'temps' => time(),
                'type' => '0',
            ]);

            $mail = ['type' => 0, 'temps' => time(), 'email' => $mails->email];
        } 
        else 
        {
            User::where('id', '=', $userId)->update(['mail' => $mails->email]);
            MailConfirm::where('user_id', '=', $userId)->delete();
        }

        $message = [
            'check' => $mail
        ];

        return $this->jsonResponse($response, $message);
    }
}
