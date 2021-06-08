<?php
namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Models\Groups;
use App\Models\MessengerFriendships;
use App\Models\User;
use App\Models\UserBadges;
use App\Models\UserStats;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class ProfilController extends DefaultController
{
    public function get(Request $request, Response $response, array $args): Response
    {
        $cacheData = $this->cache->get(10);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        $profil = User::select('id', 'username', 'look', 'motto', 'online', 'jetons', 'vip_points', 'account_created', 'last_offline')->where('username', $args['name'])->first();

        if (!$profil) {
            throw new Exception('not-found', 404);
        }

        $stats = UserStats::where('id', $profil->id)->select('respect', 'achievement_score', 'online_time')->first();

        if (!$stats) {
            throw new Exception('not-found', 404);
        }

        $badgeCount = UserBadges::where('user_id', $profil->id)->count();

        $totalPage = 0;

        if ($badgeCount > 0) {
            $totalPage = ceil($badgeCount / 40);
        }

        $groupe = Groups::where('owner_id', $profil->id)->orderBy('id')->get();

        $countcoeur = MessengerFriendships::where('user_one_id', $profil->id)->where('relation', '1')->count();
        $countami   = MessengerFriendships::where('user_one_id', $profil->id)->where('relation', '2')->count();
        $countdead  = MessengerFriendships::where('user_one_id', $profil->id)->where('relation', '3')->count();

        $randomcoeur = null;
        $randomami = null;
        $randomdead = null;

        if ($countcoeur > 0) {
            $randomcoeur = MessengerFriendships::where('user_one_id', $profil->id)->where('relation', '1')->leftJoin('users', 'messenger_friendships.user_two_id', '=', 'users.id')
                ->inRandomOrder()->limit(1)->select('users.username')->first();
        }

        if ($countami > 0) {
            $randomami = MessengerFriendships::where('user_one_id', $profil->id)->where('relation', '2')->leftJoin('users', 'messenger_friendships.user_two_id', '=', 'users.id')
                ->inRandomOrder()->limit(1)->select('users.username')->first();
        }

        if ($countdead > 0) {
            $randomdead = MessengerFriendships::where('user_one_id', $profil->id)->where('relation', '3')->leftJoin('users', 'messenger_friendships.user_two_id', '=', 'users.id')
                ->inRandomOrder()->limit(1)->select('users.username')->first();
        }

        $message = [
            'user' => $profil,
            'stats' => $stats,
            'groupe' => $groupe,
            'totalPage' => $totalPage,
            'badgecount' => $badgeCount,
            'countcoeur' => $countcoeur,
            'countami' => $countami,
            'countdead' => $countdead,
            'randomcoeur' => $randomcoeur,
            'randomami' => $randomami,
            'randomdead' => $randomdead,
        ];

        $this->cache->save($message);

        return $this->jsonResponse($response, $message);
    }

    public function getBadges(Request $request, Response $response, array $args): Response
    {
        $currentPage = 1;
        if (!empty($_GET['page']) && is_numeric($_GET['page'])) {
            $currentPage = intval($_GET['page']);
        }

        if (empty($args['userId']) || !is_numeric($args['userId'])) 
            return $this->jsonResponse($response, ['badgescode' => []]);

        $badgeList = UserBadges::where('user_id', $args['userId'])->select('badge_id')->forPage($currentPage, 40)->get();

        $message = [
            'badgescode' => $badgeList
        ];

        return $this->jsonResponse($response, $message);
    }
}
