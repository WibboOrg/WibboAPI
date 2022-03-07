<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\Room;
use App\Models\LogStaff;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Helper\Utils;
use Exception;

class TransfertRoomController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
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

        $room = Room::where('id', $roomid)->select('id', 'owner')->first();
        if (!$room) {
            throw new Exception('error', 400);
        }

        if($user->rank < 11 && $room->owner != $user->username) { // Autorizhation, cf. JasonDhose
            throw new Exception('permission', 400);
        }

        $userTarget = User::where('username', $username)->select('username')->first();
        if (!$userTarget) {
            throw new Exception('admin.user-notfound', 400);
        }

        Room::where('id', $roomid)->update(['owner' => $userTarget->username]);
        Utils::sendMusCommand('unload', $room->id);

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Transfert de l\'appartment n°: ' . $room->id. ' chez ' . $userTarget->username,
            'date' => time(),
        ]);

        return $this->jsonResponse($response, []);
    }
}