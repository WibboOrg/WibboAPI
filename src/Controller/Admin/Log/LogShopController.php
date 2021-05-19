<?php
namespace App\Controller\Admin\Log;

use App\Controller\DefaultController;
use App\Models\BoutiqueLog;
use App\Models\User;
use Exception;

class LogShopController extends DefaultController
{
    public function get($request, $response)
    {
        $input = $request->getParsedBody();
		$userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
		if(!$user) throw new Exception('disconnect', 401);
		
        if ($user->rank < 8) {
			throw new Exception('permission', 403);
        }

        $shopLogs = BoutiqueLog::join('users', 'users.id', 'cms_boutique_logs.userid')->orderBy('date', 'DESC')->select('users.username', 'cms_boutique_logs.achat', 'cms_boutique_logs.date')->limit(100)->get();
	
		$message = [
			'achat' => $shopLogs
        ];

        return $this->jsonResponse($response, $message);
    }

    public function post($request, $response)
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

		$achat = BoutiqueLog::where('userid', $userTarget->id)->orderBy('date', 'DESC')->get();
		
		$message = [
            'achat' => $achat
        ];

        return $this->jsonResponse($response, $message);
    }
}