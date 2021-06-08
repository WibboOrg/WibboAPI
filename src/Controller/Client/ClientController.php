<?php
namespace App\Controller\Client;

use App\Controller\DefaultController;
use App\Models\User;
use App\Models\UserWebSocket;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Helper\Utils;
use Exception;

class ClientController extends DefaultController
{
    public function getConfig(Request $request, Response $response, array $args): Response
    {
        $config = array();
        $config['cache'] = getenv('CLIENT_CACHE');
        $config['ip'] = getenv('CLIENT_IP');
        $config['port'] = getenv('CLIENT_PORT');
        $config['UrlWibbo'] = getenv('CLIENT_URL');
        $config['Vars'] = getenv('CLIENT_VARS');
        $config['Texts'] = getenv('CLIENT_TEXTS');
        $config['Producdata'] = getenv('CLIENT_PRODUC');
        $config['Furnidata'] = getenv('CLIENT_FURNI');

        $config['MessageFun'] = "Chargement des messages amusants...Veuillez patienter/Chargement de l'univers pixélisé/Ajustement de la température du Lido/Nettoyage des lieux publics";

        $config['Message'] = "Chargement...";
        $config['R_64'] = getenv('CLIENT_R64');
        $config['swf'] = getenv('CLIENT_SWF');

        $config['WSUrl'] = getenv('CLIENT_WS');

        $message = [
            'config' => $config
        ];

        return $this->jsonResponse($response, $message);
    }

    public function getData(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $ticket = Utils::ticketRefresh();
        $ipcountry = (!empty($_SERVER["HTTP_CF_IPCOUNTRY"]) ? $_SERVER["HTTP_CF_IPCOUNTRY"] : '');

        User::where('id', $userId)->update([
            'auth_ticket' => $ticket,
            'last_offline' => time(),
            'ip_last' => Utils::getUserIP(),
            'ipcountry' => $ipcountry,
        ]);

        $message = [
            'SSOTicket' => $ticket
        ];

        return $this->jsonResponse($response, $message);
    }

    public function getSsoTicketWeb(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();

        if(!$user) throw new Exception('disconnect', 401);

        $ticketWeb = Utils::ticketRefresh();

        UserWebSocket::updateOrCreate(
            ['user_id' => $userId],
            ['user_id' => $userId,
                'auth_ticket' => $ticketWeb,
                'is_staff' => ($user->rank >= 6) ? '1' : '0',
                'langue' => 'fr',
            ]);

        $message = array(
            'SSOTicketweb' => $ticketWeb
        );

        return $this->jsonResponse($response, $message);
    }
}