<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\Ban;
use App\Models\LogStaff;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Helper\Utils;
use Exception;

class BanController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
		$input = $request->getParsedBody();
		$userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['username', 'reason', 'time', 'type']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
		if(!$user) throw new Exception('disconnect', 401);
		
        if ($user->rank < 6) {
			throw new Exception('permission', 403);
		}
		
        $name = $data->username;
        $reason = $data->reason;
        $date = $data->time;
        $type = $data->type;

        if (empty($name) || empty($reason) || empty($date) || !is_numeric($date) || !is_numeric($type)) {
            throw new Exception('error', 400);
        }

        $userTarget = User::where('username', $name)->select('id', 'rank', 'username', 'ip_last')->first();

        if (!$userTarget) {
            throw new Exception('admin.user-notfound', 400);
        }

        if ($userTarget->rank >= $user->rank) {
            throw new Exception('permission', 400);
        }

        Ban::insert([
            'bantype' => 'user',
            'value' => $userTarget->username,
            'reason' => $reason,
            'expire' => time() + ($date * 3600),
            'added_by' => $user->username,
            'added_date' => time(),
        ]);

        if($type == 2)
        {
            Ban::insert([
                'bantype' => 'ip',
                'value' => $userTarget->ip_last,
                'reason' => $reason,
                'expire' => time() + ($date * 3600),
                'added_by' => $user->username,
                'added_date' => time(),
            ]);
        }

        User::where('id', $userTarget->id)->update(['auth_ticket' => '', 'is_banned' => '1']);

        Utils::sendMusCommand('signout', $userTarget->id);

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Bannisement de ' . $name,
            'date' => time()
        ]);

        return $this->jsonResponse($response, []);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 11) {
            throw new Exception('permission', 403);
        }

        $name = $args['username'];

        if (empty($name)) {
            throw new Exception('error', 400);
        }

        $userTarget = User::where('username', $name)->select('id')->first();

        if (!$userTarget) {
            throw new Exception('admin.user-notfound', 400);
        }

        $ban = Ban::where('value', $name)->where('bantype', 'user')->where('expire', '>', time())->select('added_by')->first();
        if (!$ban) {
            throw new Exception('error', 400);
        }

        if (($ban->added_by == "Kodamas" || $ban->added_by == "Jason" || $ban->added_by == "Hollow") && $user->rank < 12) {
            throw new Exception('permission', 400);

            LogStaff::insert([
                'pseudo' => $user->username,
                'action' => 'Tentative de bannisement de ' . $name,
                'date' => time(),
            ]);
        }

        Ban::where('value', $name)->where('bantype', 'user')->where('expire', '>', time())->update(['expire' => time()]);

        User::where('id', $userTarget->id)->update(['is_banned' => '0']);

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Débannisement de: ' . $name,
            'date' => time()
        ]);

        return $this->jsonResponse($response, []);
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 6) {
            throw new Exception('permission', 403);
        }

        $bans = Ban::orderBy('id', 'DESC')->limit(100)->get();
		
		$message = [
			'bans' => $bans
        ];

        return $this->jsonResponse($response, $message);
    }
}
