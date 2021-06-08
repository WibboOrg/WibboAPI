<?php
namespace App\Controller\User;

use App\Controller\DefaultController;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\User;
use Exception;

class UserDataController extends DefaultController
{
    public function get(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::select('id', 'username', 'motto', 'look', 'jetons', 'vip_points', 'rank', 'mail', 'block_newfriends', 'hide_online', 'hide_inroom', 'accept_trading', 'mazo', 'mazoscore', 'run_points_month', 'run_points', 'game_points_month', 'game_points')->where('id', $userId)->first();

        if(!$user) 
            throw new Exception('disconnect', 401);

        $message = [
            'user' => $user
        ];

        return $this->jsonResponse($response, $message);
    }
}