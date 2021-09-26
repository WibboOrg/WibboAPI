<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\StaffIp;
use App\Models\StaffLog;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class IpstaffController extends DefaultController
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

        $staffs = StaffIp::where('hide', '0')->where('ip', '!=', '')->get();
		
		$message = [
			'staffs' => $staffs
        ];

        return $this->jsonResponse($response, $message);
    }

    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['id', 'ip']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $id = $data->id;
        $ip = $data->ip;

        $staff = StaffIp::where('id', $id)->first();

        if (!$staff) {
            throw new Exception('error', 400);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP) === false && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            throw new Exception('error', 400);
        }

        StaffIp::where('id', $id)->update([
            'ip' => $ip,
        ]);

        StaffLog::insert([
            'pseudo' => $user->username,
            'action' => 'Changement de l\'IP de: ' . $staff->username,
            'date' => time(),
        ]);

        return $this->jsonResponse($response, []);
    }
}
