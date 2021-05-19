<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\StaffLog;
use App\Models\User;
use Exception;

class UserAccountController extends DefaultController
{
    public function post($request, $response)
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
		$machineid = "";

        if (filter_var($ip, FILTER_VALIDATE_IP) === false && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            $username = $search;
        }
        else {
            $ip = $search; 
        }
		
        if (empty($ip) && empty($username)) {
            throw new Exception('error', 400);
        }

        if (isset($username)) {
            $userTarget = User::where('username', $username)->select('ip_last', 'machine_id')->first();
            if (!$userTarget) {
                throw new Exception('admin.user-notfound', 400);
            }
            
            $ip = $userTarget->ip_last;
            $machineid = $userTarget->machine_id;
        }

        if (empty($ip)) {
            throw new Exception('error', 400);
        }

        $users = null;

        if (empty($machineid)) {
            $users = User::where('ip_last', $ip)->select('username', 'id', 'online', 'ipcountry')->get();
        } else {
            $users = User::where('ip_last', $ip)->orWhere('machine_id', $machineid)->select('username', 'id', 'online', 'ipcountry')->get();
        }

        if (!$users) {
            throw new Exception('error', 400);
        }

        if (isset($username)) {
            StaffLog::insert([
                'pseudo' => $user->username,
                'action' => 'Recherche double compte de: ' . $username,
                'date' => time(),
            ]);
        } else {
            StaffLog::insert([
                'pseudo' => $user->username,
                'action' => 'Recherche double compte de: ' . $ip,
                'date' => time(),
            ]);
        }

        foreach ($users as $user) {
            if ($user->username == 'Zeers' || $user->username == 'Jason') {
                throw new Exception('error', 400);
            }
        }
		
		$message = [
			'users' => $users
        ];

        return $this->jsonResponse($response, $message);
    }
}
