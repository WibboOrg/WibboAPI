<?php

namespace App\Middleware;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager;
use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;
use App\Models\Bans;
use App\Models\User;
use App\Models\StaffIp;
use App\Helper\Utils;

class AuthMiddleware
{
    private ContainerInterface $container;

    private Manager $db;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;

        $this->db = $container->get('db');
    }

    public function __invoke(Request $request, Response $response, $next): ResponseInterface
    {
        $jwtHeader = $request->getHeaderLine('Authorization');
        if (empty($jwtHeader) === true) {
            throw new Exception('disconnect', 401);
        }
        
        $jwt = explode('Bearer ', $jwtHeader);
        if (!!empty($jwt[1])) {
            throw new Exception('disconnect', 401);
        }

        $decoded = $this->checkToken($jwt[1]);
        $object = $request->getParsedBody();
        $object['decoded'] = $decoded;

        $userId = (int) $object['decoded']->sub;

        $userIP = (string) $object['decoded']->ip;

        $this->checkBan($userId, $userIP);

        // $this->checkIpStaff($userId);

        return $next($request->withParsedBody($object), $response);
    }

    /**
     * @param string $token
     * @return mixed
     * @throws Exception
     */
    public function checkToken(string $token): object
    {
        try {
            $decoded = JWT::decode($token, getenv('SECRET_KEY'), ['HS256']);
            if (is_object($decoded) && !empty($decoded->sub)) {// && ($decoded->ip == getUserIP() || $decoded->ip == '127.0.0.1')) {
                return $decoded;
            }
            throw new Exception('disconnect', 401);
        } catch (\UnexpectedValueException $e) {
            throw new Exception('disconnect', 401);
        } catch (\DomainException $e) {
            throw new Exception('disconnect', 401);
        }
    }

     /**
     * @param int $id
     * @return void
     * @throws Exception
     */
    public function checkBan(int $userId, string $userIP): void
    {
        if (!Utils::ipInRange(Utils::getUserIP(), "45.33.128.0/20") && !Utils::ipInRange(Utils::getUserIP(), "107.178.36.0/20")) {
            $ipBan = Bans::select('id')->where('bantype', 'ip')->where('value', '=', Utils::getUserIP())->where('expire', '>', time())->first();
            if ($ipBan) {
                throw new Exception('disconnect', 401);
            }
            
            $ipBanToken = Bans::select('id')->where('bantype', 'ip')->where('value', '=', $userIP)->where('expire', '>', time())->first();
            if ($ipBanToken) {
                throw new Exception('disconnect', 401);
            }
        }

        $user = User::select('username')->where('id', $userId)->first();
        if (!$user) {
            throw new Exception('disconnect', 401);
        }

        $accountBan = Bans::select('id')->where('bantype', 'user')->where('value', '=', $user->username)->where('expire', '>', time())->first();
        if ($accountBan) {
            throw new Exception('disconnect', 401);
        }
    }
}