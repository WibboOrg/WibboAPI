<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\LogStaff;
use App\Models\Staff;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class StaffsController extends DefaultController
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

        $staffs = Staff::Join('user', 'cms_staff.userid', 'user.id')->select('user.username', 'cms_staff.*')->get();

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

        $this->requireData($data, ['userid', 'function', 'social_insta', 'social_discord']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
        
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $targetId = $data->userid;
        $function = $data->function;

        if (empty($targetId) || empty($function)) {
            throw new Exception('error', 400);
        }

        Staff::where('userid', $targetId)->update([
            'function' => $function,
        ]);

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Mise à jour du staff : ' . $targetId,
            'date' => time(),
        ]);

        return $this->jsonResponse($response, []);
    }
}
