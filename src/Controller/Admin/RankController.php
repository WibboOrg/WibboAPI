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

        $isDerank = false;

        switch ($poste) {
            case 'co-gestion':
                if ($user->rank < 12) {
                    throw new Exception('permission', 403);
                }

                User::where('id', $userTarget->id)->update(['rank' => '11']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '6', 'function' => '']); //mettre manuellement la fonction
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "ADM",
                    'badge_slot' => '0',
                ]);
                break;
            case 'admin':
                if ($user->rank < 12) {
                    throw new Exception('permission', 403);
                }

                User::where('id', $userTarget->id)->update(['rank' => '8']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '6', 'function' => '']);//mettre manuellement la fonction
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "ADM",
                    'badge_slot' => '0',
                ]);
                break;
            case 'animateur':
                User::where('id', $userTarget->id)->update(['rank' => '7']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '8', 'function' => '']); //mettre manuellement la fonction
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "ADM",
                    'badge_slot' => '0',
                ]);
                break;
            case 'animateur-casino':
                User::where('id', $userTarget->id)->update(['rank' => '7']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '5', 'function' => '']); //mettre manuellement la fonction
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "CRPOFFI",
                    'badge_slot' => '0',
                ]);
                break;
            case 'modo':
                User::where('id', $userTarget->id)->update(['rank' => '6']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '3', 'function' => '']); //mettre manuellement la fonction
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "ADM",
                    'badge_slot' => '0',
                ]);
                break;
            case 'helpeur':
                User::where('id', $userTarget->id)->update(['rank' => '4']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '2', 'function' => '']); //mettre manuellement la fonction
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "wibbo.helpeur",
                    'badge_slot' => '0',
                ]);
                break;
            case 'graphiste':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '4', 'function' => '']); //mettre manuellement la fonction
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "GPHWIB",
                    'badge_slot' => '0',
                ]);
                break;
            case 'arch':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '1', 'function' => '']); //mettre manuellement la fonction
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "WIBARC",
                    'badge_slot' => '0',
                ]);
                break;
            case 'wired':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '1', 'function' => '']); //mettre manuellement la fonction
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "PRWRD1",
                    'badge_slot' => '0',
                ]);
                break;
            case 'croupier':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '5', 'function' => '']); //mettre manuellement la fonction
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "CRPOFFI",
                    'badge_slot' => '0',
                ]);
                break;
            case 'radio':
                User::where('id', $userTarget->id)->update(['rank' => '3']);
                Staff::insert(['userid' => $userTarget->id, 'rank' => '7', 'function' => '']); //mettre manuellement la fonction
                StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                UserBadge::insert([
                    'user_id' => $userTarget->id,
                    'badge_id' => "ZEERSWS",
                    'badge_slot' => '0',
                ]);
                break;

            case 'communication':
                    User::where('id', $userTarget->id)->update(['rank' => '3']);
                    Staff::insert(['userid' => $userTarget->id, 'rank' => '9', 'function' => '']); //mettre manuellement la fonction
                    StaffProtect::insert(['id' => $userTarget->id, 'ip' => 'IP', 'username' => $userTarget->username]);
                    UserBadge::insert([
                        'user_id' => $userTarget->id,
                        'badge_id' => "WIBBOCOM",
                        'badge_slot' => '0',
                    ]);
                break;
                
            case 'joueur':
                if ($userTarget->rank > 12) {
                    throw new Exception('permission', 400);
                }
        
                if ($user->rank < 12 && $userTarget->rank >= 8) {
                    throw new Exception('permission', 403);
                }
        
                User::where('id', $userTarget->id)->update(['rank' => '1']);
                Staff::where('userid', $userTarget->id)->delete();
                UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'ADM')->delete();
                UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'CRPOFFI')->delete();
                UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'WIBARC')->delete();
                UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'wibbo.helpeur')->delete();
                UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'GPHWIB')->delete();
                UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'ZEERSWS')->delete();
                UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'PRWRD1')->delete();
                UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'WIBBOCOM')->delete();
                StaffProtect::where('id', $userTarget->id)->delete();

                $isDerank = true;
                break;
        }

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => (($isDerank) ? 'Rank' : 'Derank') . ' de l\'utilisateur: ' . $username,
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

        if ($user->rank < 12 && $userTarget->rank >= 8) {
            throw new Exception('permission', 403);
        }

        if ($userTarget->rank > 2) {
            User::where('id', $userTarget->id)->update(['rank' => '1']);
        }

        StaffProtect::where('id', $userTarget->id)->delete();
        Staff::where('userid', $userTarget->id)->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'ADM')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'CRPOFFI')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'WIBARC')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'wibbo.helpeur')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'GPHWIB')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'ZEERSWS')->delete();
        UserBadge::where('user_id', $userTarget->id)->where('badge_id', 'PRWRD1')->delete();

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Derank de l\'utilisateur: ' . $username,
            'date' => time(),
        ]);

        return $this->jsonResponse($response, []);
    }
}