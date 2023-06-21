<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\LogStaff;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class UserAccountController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['search']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 6) {
            throw new Exception('permission', 403);
        }

        $search = $data->search;

        $username = "";
        $ip = "";

        if (filter_var($search, FILTER_VALIDATE_IP) !== false || filter_var($search, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $ip = $search;
        }
        else {
            $username = $search; 
        }

        if (empty($ip) && empty($username)) {
            throw new Exception('error', 400);
        }

        if (!empty($username)) {
            $userTarget = User::where('username', $username)->select('ip_last', 'machine_id')->first();
            if (!$userTarget) {
                throw new Exception('admin.user-notfound', 400);
            }
            
            $ip = $userTarget->ip_last;
        }

        if (empty($ip)) {
            throw new Exception('error', 400);
        }

        $users = User::where('ip_last', $ip)->select('username', 'id', 'online', 'ipcountry', 'rank')->get();

        if (!$users) {
            throw new Exception('error', 400);
        }

        if (!empty($username)) {
            LogStaff::insert([
                'pseudo' => $user->username,
                'action' => 'Recherche les doubles compte de : ' . $username,
                'date' => time(),
            ]);
        } else {
            LogStaff::insert([
                'pseudo' => $user->username,
                'action' => 'Recherche les doubles compte sur l\'IP : ' . $ip,
                'date' => time(),
            ]);
        }

        if ($user->rank < 11) {
            foreach ($users as $userTarget) {
                if ($userTarget->rank >= 11) {
                    throw new Exception('error', 400);
                }
            }
        }
		
		$message = [
			'users' => $users
        ];

        return $this->jsonResponse($response, $message);
    }
}
