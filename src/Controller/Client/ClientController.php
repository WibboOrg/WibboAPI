<?php
namespace App\Controller\Client;

use App\Controller\DefaultController;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Helper\Utils;

class ClientController extends DefaultController
{
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
}