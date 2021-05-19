<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\Rooms;
use App\Models\StaffLog;
use App\Models\User;
use Exception;

class TransfertRoomController extends DefaultController
{
    public function post($request, $response)
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['username', 'roomid']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $username = $data->username;
        $roomid = $data->roomid;

        if (empty($username) || empty($roomid) || !is_numeric($roomid)) {
            throw new Exception('error', 400);
        }

        $room = Rooms::where('id', $roomid)->select('id', 'owner')->first();
        if (!$room) {
            throw new Exception('error', 400);
        }

        if($user->rank < 12 && $room->owner != $user->username) {
            throw new Exception('permission', 400);
        }

        $userTarget = User::where('username', $username)->select('username')->first();
        if (!$userTarget) {
            throw new Exception('admin.user-notfound', 400);
        }

        Rooms::where('id', $roomid)->update(['owner' => $userTarget->username]);
        sendMusCommand('unload', $room->id);

        StaffLog::insert([
            'pseudo' => $user->username,
            'action' => 'Transfert de l\'appart: ' . $room->id . ' chez ' . $userTarget->username,
            'date' => time(),
        ]);
    }
}