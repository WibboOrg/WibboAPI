<?php
namespace App\Controller\Auth;

use App\Controller\DefaultController;
use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;
use App\Models\User;
use App\Models\StaffProtect;
use App\Models\LogLogin;
use App\Models\Ban;
use App\Helper\Utils;
use Exception;

class AuthController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $jwt = $this->login($input);
        $message = [
            'Authorization' => 'Bearer ' . $jwt,
        ];

        return $this->jsonResponse($response, $message);
    }

    private function login(array $input): string
    {
        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['username', 'password']);

        $user = $this->loginUser($data->username, $data->password);

        LogLogin::insert([
            'user_id' => $user->id,
            'date' => time(),
            'ip' => Utils::getUserIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ]);

        $this->checkBan($data->username, $user->id, ($user->is_banned == '1'));
        $this->checkIpStaff($user->id);

        $ipcountry = (!empty($_SERVER["HTTP_CF_IPCOUNTRY"]) ? $_SERVER["HTTP_CF_IPCOUNTRY"] : '');

        $token = [
            'sub' => $user->id,
            'country' => $ipcountry,
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60),
            'ip' => Utils::getUserIP()
        ];

        User::where('id', $user->id)->update([
            'last_offline' => time(),
            'ip_last' => Utils::getUserIP(),
            'ipcountry' => $ipcountry,
            'langue' => 'fr'
        ]);

        return JWT::encode($token, getenv('SECRET_KEY'));
    }

    private function loginUser(string $username, string $password): User
    {
        $user = User::where('username', $username)->select('id', 'is_banned', 'password')->first();

        if(!$user) {
            throw new Exception('login.fail', 400);
        }

        if(!password_verify($password, $user->password) && Utils::hashMdp($password, true) != $user->password) {
            throw new Exception('login.fail', 400);
        }

        return $user;
    }

    private function checkIpStaff(int $userId): void
    {
        $protectionstaff = StaffProtect::where('id', $userId)->first();
        if (!$protectionstaff) return;

        if ($protectionstaff->ip == Utils::getUserIP()) return;
        
        throw new Exception('login.staff|' . Utils::getUserIP(), 400);
    }

    private function checkBan(string $username, int $userId, bool $isBanned): void
    {
        if (!Utils::ipInRange(Utils::getUserIP(), "45.33.128.0/20") && !Utils::ipInRange(Utils::getUserIP(), "107.178.36.0/20")) {
            $ipBan = Ban::select('reason', 'expire')->where('bantype', 'ip')->where('value', Utils::getUserIP())->where('expire', '>', time())->first();
            if ($ipBan) {
                throw new Exception('login.ban|'.$ipBan->reason.'|'.date('d/m/Y|H:i:s', $ipBan->expire), 400);
            }
        }

        $accountBan = Ban::select('reason', 'expire')->where('bantype', 'user')->where('value', $username)->where('expire', '>', time())->first();
        if ($accountBan) {
            throw new Exception('login.ban|'.$accountBan->reason.'|'.date('d/m/Y|H:i:s', $accountBan->expire), 400);
        }

        if($isBanned)
        {
            User::where('id', $userId)->update(['is_banned' => '0']);
        }
    }
}