<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\StaffLog;
use App\Models\User;
use App\Models\UserBadges;
use Exception;

class BadgeController extends DefaultController
{
    public function post($request, $response)
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['username', 'code']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $pseudo = $data->username;
        $badgecode = $data->code;

        if (empty($pseudo) || empty($badgecode)) {
            throw new Exception('error', 400);
        }

        foreach (explode(' ', $pseudo) as $value) {

            $userTarget = User::where('username', $value)->select('id', 'username')->first();

            if (!$userTarget) {
                continue;
            }

            $badge = UserBadges::where('user_id', $userTarget->id)->where('badge_id', $badgecode)->first();
            if ($badge) {
                continue;
            }

            UserBadges::insert([
                'user_id' => $userTarget->id,
                'badge_id' => $badgecode,
                'badge_slot' => '0',
            ]);
        }

        StaffLog::insert([
            'pseudo' => $user->username,
            'action' => 'Envoie du badge: ' . $badgecode . ' à ' . $pseudo,
            'date' => time(),
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

        $username = $args["username"];
        $badgecode = $args["code"];

        if (empty($username) || empty($badgecode)) {
            throw new Exception('error', 400);
        }

        $userTarget = User::where('username', $username)->select('id', 'username')->first();

        if (!$userTarget) {
            throw new Exception('admin.user-notfound', 400);
        }

        $badge = UserBadges::where('user_id', $userTarget->id)->where('badge_id', $badgecode)->first();
        if (!$badge) {
            throw new Exception('error', 400);
        }

        UserBadges::where('user_id', $userTarget->id)->where('badge_id', $badgecode)->delete();

        StaffLog::insert([
            'pseudo' => $user->username,
            'action' => 'Suppression du badge: ' . $badgecode . ' à ' . $userTarget->username,
            'date' => time(),
        ]);
    }

    public function count($request, $response)
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['code']);

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 6) {
            throw new Exception('permission', 403);
        }

        $badge = $data->code;

        if (empty($badge)) {
            throw new Exception('error', 400);
        }

        $badgecount = UserBadges::where('badge_id', $badge)->count();

		$message = [
			'count' => $badgecount
        ];

        return $this->jsonResponse($response, $message);
    }
}