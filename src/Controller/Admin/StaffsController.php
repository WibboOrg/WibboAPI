<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\StaffLog;
use App\Models\StaffPage;
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

        $staffs = StaffPage::Join('users', 'cms_page_staff.userid', 'users.id')->select('users.username', 'cms_page_staff.*')->get();

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
        $insta = $data->social_insta;
        $discord = $data->social_discord;

        if (empty($targetId) || empty($function)) {
            throw new Exception('error', 400);
        }

        StaffPage::where('userid', $targetId)->update([
            'function' => $function,
            'social_insta' => $insta,
            //'social_discord' => $discord,
        ]);

        StaffLog::insert([
            'pseudo' => $user->username,
            'action' => 'Mise à jour du staff : ' . $targetId,
            'date' => time(),
        ]);

        return $this->jsonResponse($response, []);
    }
}
