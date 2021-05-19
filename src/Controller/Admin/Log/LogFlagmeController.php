<?php
namespace App\Controller\Admin\Log;

use App\Controller\DefaultController;
use App\Models\LogFlagme;
use App\Models\User;
use Exception;

class LogFlagmeController extends DefaultController
{
    public function post($request, $response)
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

        $logs = LogFlagme::where('newusername', $username)->orWhere('oldusername', $username)->orderBy('time', 'DESC')->limit(100)->get();
		
		$message = [
			'logs' => $logs
        ];

        return $this->jsonResponse($response, $message);
    }
}
