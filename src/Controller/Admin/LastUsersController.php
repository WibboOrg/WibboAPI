<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class LastUsersController extends DefaultController
{
    public function get(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 6) {
            throw new Exception('permission', 403);
        }

        $users = User::orderBy('id', 'DESC')->limit(50)->select('id', 'username', 'online', 'ip_last', 'ipcountry')->get();

        $message = [
            'users' => $users
        ];

        return $this->jsonResponse($response, $message);
    }
}