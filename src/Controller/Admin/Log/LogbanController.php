<?php
namespace App\Controller\Admin\Log;

use App\Controller\DefaultController;
use App\Models\Bans;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class LogbanController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['username']);

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 6) {
            throw new Exception('permission', 403);
        }

        $username = $data->username;

        if (empty($username)) {
            throw new Exception('error', 400);
        }

        $userTarget = User::where('username', $username)->select('username', 'ip_last')->first();

        if (!$userTarget) {
            throw new Exception('admin.user-notfound', 400);
        }

        $bans = Bans::where('value', $userTarget->username)->where('bantype', 'user')->orWhere('value', $userTarget->ip_last)->where('bantype', 'ip')->orderBy('id', 'DESC')->get();
		
		$message = [
			'bans' => $bans
        ];

        return $this->jsonResponse($response, $message);
    }
}