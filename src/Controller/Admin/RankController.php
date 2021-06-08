<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\StaffIp;
use App\Models\StaffLog;
use App\Models\StaffPage;
use App\Models\User;
use App\Models\UserBadges;
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
                
        if ($user->rank < 12) {
            throw new Exception('permission', 403);
        }

        $username = $data->username;
        $poste = $data->rank;

        if (empty($username) || empty($poste)) {
            throw new Exception('error', 400);
        }

        $userTarget = User::where('username', $username)->select('id', 'username', 'rank', 'mail_valide', 'look', 'account_created')->first();
        if (!$userTarget) {
            throw new Exception('admin.user-notfound', 400);
        }

        if ($userTarget->rank > 2) {
            throw new Exception('error', 400);
        }

        if ($userTarget->mail_valide == '0') {
            throw new Exception('error', 400);
        }

        switch ($poste) {
            case 'admin':
                User::where('id', $userTarget->id)->update(['rank' => '8']);
                StaffPage::insert(['userid' => $userTarget->id, 'rank' => '6', 'function' => 'Administrateur']);
                StaffIp::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadges::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "ADM",
                    'badge_slot' => '0',
                ]);
                break;
            case 'animateur':
                User::where('id', $userTarget->id)->update(['rank' => '7']);
                StaffPage::insert(['userid' => $userTarget->id, 'rank' => '8', 'function' => 'Animateur']);
                StaffIp::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadges::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "ADM",
                    'badge_slot' => '0',
                ]);
                break;
            case 'animateur-casino':
                User::where('id', $userTarget->id)->update(['rank' => '7']);
                StaffPage::insert(['userid' => $userTarget->id, 'rank' => '5', 'function' => 'Animateur casino']);
                StaffIp::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadges::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "CRPOFFI",
                    'badge_slot' => '0',
                ]);
                break;
            case 'modo':
                User::where('id', $userTarget->id)->update(['rank' => '6']);
                StaffPage::insert(['userid' => $userTarget->id, 'rank' => '3', 'function' => 'Modérateur']);
                StaffIp::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadges::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "ADM",
                    'badge_slot' => '0',
                ]);
                break;
            case 'helpeur':
                User::where('id', $userTarget->id)->update(['rank' => '4']);
                StaffPage::insert(['userid' => $userTarget->id, 'rank' => '2', 'function' => 'Helpeur']);
                StaffIp::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadges::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "wibbo.helpeur",
                    'badge_slot' => '0',
                ]);
                break;
            case 'spec':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                StaffPage::insert(['userid' => $userTarget->id, 'rank' => '7', 'function' => 'Spécial']);
                UserBadges::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "GPHWIB",
                    'badge_slot' => '0',
                ]);
                break;
            case 'arch':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                StaffPage::insert(['userid' => $userTarget->id, 'rank' => '1', 'function' => 'Architect']);
                UserBadges::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "WIBARC",
                    'badge_slot' => '0',
                ]);
                break;
            case 'wired':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                StaffPage::insert(['userid' => $userTarget->id, 'rank' => '4', 'function' => 'Pro Wired']);
                UserBadges::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "WIBARC",
                    'badge_slot' => '0',
                ]);
                break;
            case 'croupier':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                StaffPage::insert(['userid' => $userTarget->id, 'rank' => '5', 'function' => 'Croupier']);
                UserBadges::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "CRPOFFI",
                    'badge_slot' => '0',
                ]);
                break;
            case 'radio':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                StaffPage::insert(['userid' => $userTarget->id, 'rank' => '8', 'function' => 'Animateur radio']);
                UserBadges::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "ZEERSWS",
                    'badge_slot' => '0',
                ]);
                break;
        }

        StaffLog::insert([
            'pseudo' => $user->username,
            'action' => 'Rank de l\'utilisateur: ' . $userTarget->username,
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
                
        if ($user->rank < 12) {
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

        if ($userTarget->rank > 2) {
            User::where('id', $userTarget->id)->update(['rank' => '1']);
        }

        StaffIp::where('id', $userTarget->id)->delete();
        StaffPage::where('userid', $userTarget->id)->delete();
        UserBadges::where('user_id', $userTarget->id)->where('badge_id', 'ADM')->delete();
        UserBadges::where('user_id', $userTarget->id)->where('badge_id', 'CRPOFFI')->delete();
        UserBadges::where('user_id', $userTarget->id)->where('badge_id', 'WIBARC')->delete();
        UserBadges::where('user_id', $userTarget->id)->where('badge_id', 'wibbo.helpeur')->delete();
        UserBadges::where('user_id', $userTarget->id)->where('badge_id', 'GPHWIB')->delete();
        UserBadges::where('user_id', $userTarget->id)->where('badge_id', 'ZEERSWS')->delete();

        StaffLog::insert([
            'pseudo' => $user->username,
            'action' => 'Derank de l\'utilisateur: ' . $username,
            'date' => time(),
        ]);

        return $this->jsonResponse($response, []);
    }
}