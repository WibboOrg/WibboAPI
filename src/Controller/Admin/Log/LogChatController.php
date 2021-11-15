<?php
namespace App\Controller\Admin\Log;

use App\Controller\DefaultController;
use App\Models\LogChat;
use App\Models\LogStaff;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class LogChatController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;
        
        $data = json_decode(json_encode($input), false);
        
        $this->requireData($data, ['username', 'startdate', 'enddate']);
        
        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 6) {
            throw new Exception('permission', 403);
        }

        $username = $data->username;
        $startdate = $data->startdate;
        $enddate = $data->enddate;

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Recherche chatlog de: ' . $username,
            'date' => time()
        ]);

        if (empty($username) || empty($startdate) || !strtotime($startdate) || empty($enddate) || !strtotime($enddate)) {
            throw new Exception('error', 400);
        }

        $timestamp = strtotime($startdate);
        $timestampEnd = strtotime($enddate);

        $chatlogs = LogChat::where('user_name', $username)->where('timestamp', '>', $timestamp)->where('timestamp', '<', $timestampEnd)->orderBy('timestamp', 'DESC')->get();
		
		$message = [
			'chatlogs' => $chatlogs
        ];

        return $this->jsonResponse($response, $message);
    }

}
