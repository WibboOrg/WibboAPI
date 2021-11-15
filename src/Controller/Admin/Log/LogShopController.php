<?php
namespace App\Controller\Admin\Log;

use App\Controller\DefaultController;
use App\Models\LogShop;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class LogShopController extends DefaultController
{
    public function get(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
		$userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
		if(!$user) throw new Exception('disconnect', 401);
		
        if ($user->rank < 8) {
			throw new Exception('permission', 403);
        }

        $shopLogs = LogShop::join('user', 'user.id', 'log_shop.userid')->orderBy('date', 'DESC')->select('user.username', 'log_shop.achat', 'log_shop.date')->limit(100)->get();
	
		$message = [
			'achat' => $shopLogs
        ];

        return $this->jsonResponse($response, $message);
    }

    public function post(Request $request, Response $response, array $args): Response
    {
		$input = $request->getParsedBody();
		$userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['username']);

        $user = User::where('id', $userId)->select('rank')->first();
		if(!$user) throw new Exception('disconnect', 401);
		
        if ($user->rank < 8) {
			throw new Exception('permission', 403);
        }

        $pseudo = $data->username;

        if (empty($pseudo)) {
            throw new Exception('error', 400);
        }

        $userTarget = User::where('username', $pseudo)->select('id')->first();

        if (!$userTarget) {
            throw new Exception('admin.user-notfound', 400);
        }

		$achat = LogShop::where('userid', $userTarget->id)->orderBy('date', 'DESC')->get();
		
		$message = [
            'achat' => $achat
        ];

        return $this->jsonResponse($response, $message);
    }
}