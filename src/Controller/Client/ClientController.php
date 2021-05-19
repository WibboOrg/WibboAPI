<?php
namespace App\Controller\Client;

use App\Controller\DefaultController;
use App\Models\User;
use App\Models\UserWebSocket;
use Exception;

class ClientController extends DefaultController
{
    public function getConfig($request, $response, $args)
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

        return $this->jsonResponse($response, $config);
    }

    public function getData($request, $response, $args)
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $ticket = ticketRefresh();
        $ipcountry = (!empty($_SERVER["HTTP_CF_IPCOUNTRY"]) ? $_SERVER["HTTP_CF_IPCOUNTRY"] : '');

        User::where('id', $userId)->update([
            'auth_ticket' => $ticket,
            'last_offline' => time(),
            'ip_last' => getUserIP(),
            'ipcountry' => $ipcountry,
        ]);

        $data = [
            'SSOTicket' => $ticket
        ];

        return $this->jsonResponse($response, $data);
    }

    public function getSsoTicketWeb($request, $response, $args)
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();

        if(!$user) throw new Exception('disconnect', 401);

        $ticketWeb = ticketRefresh();

        UserWebSocket::updateOrCreate(
            ['user_id' => $userId],
            ['user_id' => $userId,
                'auth_ticket' => $ticketWeb,
                'is_staff' => ($user->rank >= 6) ? '1' : '0',
                'langue' => 'fr',
            ]);

        $data = array(
            'SSOTicketweb' => $ticketWeb
        );

        return $this->jsonResponse($response, $data);
    }
}