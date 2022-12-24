<?php
namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Models\Ban;
use App\Models\User;
use App\Models\UserStats;
use App\Models\LogVpn;
use Firebase\JWT\JWT;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Helper\Utils;
use Exception;

class RegisterController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['username', 'password', 'repassword', 'condition', 'email', 'recaptchaToken']);

        $filter = preg_replace("/[^a-z\d\-=\?!@:\.]/i", "", $data->username);
        if ($filter !== $data->username) {
            throw new Exception('register.incorrect-username', 400);
        }

        if (strlen($data->username) > 24) {
            throw new Exception('register.big-username', 400);
        }

        if (strlen($data->username) < 3) {
            throw new Exception('register.empty-username', 400);
        }

        if ($data->password != $data->repassword) {
            throw new Exception('register.same-password', 400);
        }

        if (strlen($data->password) < 6) {
            throw new Exception('register.empty-password', 400);
        }

        if ($data->condition != "true") {
            throw new Exception('register.condition', 400);
        }

        
        if (getenv('RECAPTCHA') !== '') {
            $result = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . getenv('RECAPTCHA') . "&response=" . $data->recaptchaToken . "&remoteip=" . Utils::getUserIP());
            
            $etat = json_decode($result);
            if ($etat->success != "1") {
                throw new Exception('captcha', 400);
            }
        }
        
        $ipcountry = (!empty($_SERVER["HTTP_CF_IPCOUNTRY"]) ? $_SERVER["HTTP_CF_IPCOUNTRY"] : '');
        $userIP = Utils::getUserIP();

        if (!Utils::ipInRange($userIP, "45.33.128.0/20") && !Utils::ipInRange($userIP, "107.178.36.0/20")) {
            $ipBan = Ban::select('reason', 'expire')->where('bantype', 'ip')->where('value', $userIP)->where('expire', '>', time())->first();
            if ($ipBan) {
                throw new Exception('login.ban|'.$ipBan->reason.'|'.date('d/m/Y|H:i:s', $ipBan->expire), 400);
            }
        }

        $logVpn = LogVpn::where('ip', $userIP)->select('is_vpn')->first();
        if($logVpn && $logVpn->is_vpn === '1') {
            throw new Exception('register.vpn', 400);
        }

        if (!$logVpn) {
            $host = @gethostbyaddr($userIP);

            if (Utils::allowedFAI($host) === false) {

                $isVpn = Utils::isVPN($userIP, $host);
                
                LogVpn::insert([
                    "ip" => $userIP,
                    "ip_country" => $ipcountry,
                    "host" => $host,
                    "timestamp_created" => time(),
                    "is_vpn" => $isVpn ? "1" : "0"
                ]);

                if($isVpn)
                    throw new Exception('register.vpn', 400);
            }
        }

        $user = User::where('username', $data->username)->first();
        if ($user) {
            throw new Exception('register.exist-username', 400);
        }

        $limiteIP = User::where('ip_last', $userIP)->count();
        if ($limiteIP > 100) {
            throw new Exception('register.ip-limit', 400);
        }

        $timecreated = time() - (60 * 60 * 1);

        $limiteCreated = User::where('ip_last', $userIP)->where('account_created', '>=', $timecreated)->count();
        if ($limiteCreated > 1) {
            throw new Exception('register.create-limit', 400);
        }

        $id = User::insertGetId([
            'username' => $data->username,
            'password' => Utils::hashMdp($data->password),
            'rank' => 1,
            'gender' => 'M',
            'motto' => '',
            'credits' => 1000000,
            'activity_points' => 100,
            'last_offline' => time(),
            'account_created' => time(),
            'last_online' => time(),
            'ip_last' => $userIP,
            'ipcountry' => $ipcountry,
            'langue' => 'fr',
        ]);

        UserStats::insert(['id' => $id]);

        $token = [
            'sub' => $id,
            'country' => $ipcountry,
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60),
            'ip' => $userIP
        ];

        $jwt = JWT::encode($token, getenv('SECRET_KEY'));

        $message = [
            'Authorization' => 'Bearer ' . $jwt,
        ];

        return $this->jsonResponse($response, $message);
    }
}
