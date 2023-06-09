<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\LogFlagme;
use App\Models\Room;
use App\Models\LogStaff;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class FlagmeController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['username']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 11) {
            throw new Exception('permission', 403);
        }

        $name = $data->username;

        if (empty($name)) {
            throw new Exception('error', 400);
        }

        $twoYear = 63072000; //60 * 60 * 24 * 365 * 2;

        $userTarget = User::where('username', $name)->select('id', 'rank', 'username', 'mail', 'last_online')->first();

        if (!$userTarget) {
            throw new Exception('admin.user-notfound', 400);
        }

        if ($userTarget->rank > 1 || !empty($userTarget->mail) || $userTarget->last_online < time() - $twoYear) {
            throw new Exception('permission', 400);
        }

        $newUserame = 'old-' . time();

        User::where('id', $userTarget->id)->update(['username' => $newUserame]);
        Room::where('owner', $userTarget->username)->update(['owner' => $newUserame]);
        ForumPost::where('author', $userTarget->username)->update(['author' => $newUserame]);
        ForumThread::where('author', $userTarget->username)->update(['author' => $newUserame]);
        LogFlagme::insert([
            'user_id' => $userTarget->id,
            'oldusername' => $userTarget->username,
            'newusername' => $newUserame,
            'time' => time()
        ]);

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Libération pseudo de ' . $userTarget->username . ': ' . $userTarget->id,
            'date' => time()
        ]);

        return $this->jsonResponse($response, []);
    }
}
