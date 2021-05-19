<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\Bans;
use App\Models\StaffLog;
use App\Models\User;
use Exception;

class BanController extends DefaultController
{
    public function post($request, $response)
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

        Bans::insert([
            'bantype' => 'user',
            'value' => $userTarget->username,
            'reason' => $reason,
            'expire' => time() + ($date * 3600),
            'added_by' => $user->username,
            'added_date' => time(),
        ]);

        if($type == 2)
        {
            Bans::insert([
                'bantype' => 'ip',
                'value' => $userTarget->ip_last,
                'reason' => $reason,
                'expire' => time() + ($date * 3600),
                'added_by' => $user->username,
                'added_date' => time(),
            ]);
        }

        User::where('id', $userTarget->id)->update(['auth_ticket' => '']);

        sendMusCommand('signout', $userTarget->id);

        StaffLog::insert([
            'pseudo' => $user->username,
            'action' => 'Bannisement de: ' . $name,
            'date' => time()
        ]);

    }

    public function delete($request, $response, $args)
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $name = $args['username'];

        if (empty($name)) {
            throw new Exception('error', 400);
        }

        $ban = Bans::where('value', $name)->where('bantype', 'user')->where('expire', '>', time())->select('added_by')->first();
        if (!$ban) {
            throw new Exception('error', 400);
        }

        if (($ban->added_by == "Kodamas" || $ban->added_by == "Jason" || $ban->added_by == "Hollow") && $user->rank < 12) {
            throw new Exception('permission', 400);
        }

        Bans::where('value', $name)->where('bantype', 'user')->where('expire', '>', time())->update(['expire' => time()]);

        StaffLog::insert([
            'pseudo' => $user->username,
            'action' => 'Débannisement de: ' . $name,
            'date' => time()
        ]);
    }

    public function get($request, $response)
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 6) {
            throw new Exception('permission', 403);
        }

        $bans = Bans::orderBy('id', 'DESC')->limit(100)->get();
		
		$message = [
			'bans' => $bans
        ];

        return $this->jsonResponse($response, $message);
    }
}
