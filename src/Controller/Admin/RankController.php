<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\StaffProtect;
use App\Models\LogStaff;
use App\Models\Staff;
use App\Models\User;
use App\Models\UserBadge;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class RankController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['username', 'rank']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $username = $data->username;
        $poste = $data->rank;

        if (empty($username) || empty($poste)) {
            throw new Exception('error', 400);
        }

        $userTarget = User::where('username', $username)->select('id', 'username', 'rank', 'mail', 'look', 'account_created')->first();
        if (!$userTarget) {
            throw new Exception('admin.user-notfound', 400);
        }

        if (empty($userTarget->mail)) {
            throw new Exception('error', 400);
        }

        if ($userTarget->rank > 12) {
            throw new Exception('permission', 400);
        }

        if ($user->rank < 11 && $userTarget->rank >= 8) {
            throw new Exception('permission', 403);
        }

        if ($userTarget->rank > 2) {
            Staff::where('userid', $userTarget->id)->delete();
            UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_ADMIN')->delete();
            UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_ANIMATEUR')->delete();
            UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_EVENT')->delete();
            UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_ARCHITECTE')->delete();
            UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_CASINO')->delete();
            UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_GESTION')->delete();
            UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_GRAPH')->delete();
            UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_MODO')->delete();
            UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_PROWIRED')->delete();
            UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_HELPER')->delete();
            StaffProtect::where('id', $userTarget->id)->delete();
        }

        switch ($poste) {
            case 'admin':
                if ($user->rank < 11) {
                    throw new Exception('permission', 403);
                }

                User::where('id', $userTarget->id)->update(['rank' => '8']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '6', 'function' => '']);
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "STAFF_ADMIN",
                    'badge_slot' => '0',
                ]);
                break;
                
            case 'event':
                User::where('id', $userTarget->id)->update(['rank' => '7']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '9', 'function' => '']); 
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "STAFF_EVENT",
                    'badge_slot' => '0',
                ]);
                break;
                
            case 'animateur':
                User::where('id', $userTarget->id)->update(['rank' => '7']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '8', 'function' => '']); 
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "STAFF_ANIMATEUR",
                    'badge_slot' => '0',
                ]);
                break;

            case 'animateur-casino':
                User::where('id', $userTarget->id)->update(['rank' => '7']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '5', 'function' => '']); 
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "STAFF_CASINO",
                    'badge_slot' => '0',
                ]);
                break;

            case 'modo':
                User::where('id', $userTarget->id)->update(['rank' => '6']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '3', 'function' => '']); 
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "STAFF_MODO",
                    'badge_slot' => '0',
                ]);
                break;

            case 'graphiste':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '4', 'function' => '']); 
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "STAFF_GRAPH",
                    'badge_slot' => '0',
                ]);
                break;

            case 'helper':
                User::where('id', $userTarget->id)->update(['rank' => '4']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '1', 'function' => '']); 
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "STAFF_HELPER",
                    'badge_slot' => '0',
                ]);
                break;

            case 'arch':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '1', 'function' => '']); 
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "STAFF_ARCHITECTE",
                    'badge_slot' => '0',
                ]);
                break;

            case 'wired':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '1', 'function' => '']); 
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "STAFF_PROWIRED",
                    'badge_slot' => '0',
                ]);
                break;

            case 'croupier':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '5', 'function' => '']); 
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "STAFF_CASINO",
                    'badge_slot' => '0',
                ]);
                break;
        }

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Rank de l\'utilisateur: ' . $username,
            'date' => time(),
        ]);

        return $this->jsonResponse($response, []);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $username = $args['username'];

        if (empty($username)) {
            throw new Exception('error', 400);
        }

        $userTarget = User::where('username', $username)->select('id', 'rank')->first();
        if (!$userTarget) {
            throw new Exception('admin.user-notfound', 400);
        }

        if ($userTarget->rank > 12) {
            throw new Exception('permission', 400);
        }

        if ($user->rank < 11 && $userTarget->rank >= 8) {
            throw new Exception('permission', 403);
        }

        if ($userTarget->rank > 2) {
            User::where('id', $userTarget->id)->update(['rank' => '1']);
        }

        StaffProtect::where('id', $userTarget->id)->delete();
        Staff::where('userid', $userTarget->id)->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_ADMIN')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_ANIMATEUR')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_EVENT')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_ARCHITECTE')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_CASINO')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_GESTION')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_GRAPH')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_MODO')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_PROWIRED')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'STAFF_HELPER')->delete();

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Derank de l\'utilisateur: ' . $username,
            'date' => time(),
        ]);

        return $this->jsonResponse($response, []);
    }
}