<?php
namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Models\Groups;
use App\Models\MessengerFriendship;
use App\Models\User;
use App\Models\UserBadge;
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

        $profil = User::select('id', 'username', 'look', 'motto', 'online', 'limit_coins', 'vip_points', 'account_created', 'last_offline')->where('username', $args['name'])->first();

        if (!$profil) {
            throw new Exception('not-found', 404);
        }

        $stats = UserStats::where('id', $profil->id)->select('respect', 'achievement_score', 'online_time')->first();

        if (!$stats) {
            throw new Exception('not-found', 404);
        }

        $badgeCount = UserBadge::where('user_id', $profil->id)->count();

        $totalPage = 0;

        if ($badgeCount > 0) {
            $totalPage = ceil($badgeCount / 40);
        }

        $groupe = Groups::where('owner_id', $profil->id)->orderBy('id')->get();

        $countcoeur = MessengerFriendship::where('user_one_id', $profil->id)->where('relation', '1')->count();
        $countami   = MessengerFriendship::where('user_one_id', $profil->id)->where('relation', '2')->count();
        $countdead  = MessengerFriendship::where('user_one_id', $profil->id)->where('relation', '3')->count();

        $randomcoeur = null;
        $randomami = null;
        $randomdead = null;

        if ($countcoeur > 0) {
            $randomcoeur = MessengerFriendship::where('user_one_id', $profil->id)->where('relation', '1')->leftJoin('user', 'messenger_friendship.user_two_id', '=', 'user.id')
                ->inRandomOrder()->limit(1)->select('user.username')->first();
        }

        if ($countami > 0) {
            $randomami = MessengerFriendship::where('user_one_id', $profil->id)->where('relation', '2')->leftJoin('user', 'messenger_friendship.user_two_id', '=', 'user.id')
                ->inRandomOrder()->limit(1)->select('user.username')->first();
        }

        if ($countdead > 0) {
            $randomdead = MessengerFriendship::where('user_one_id', $profil->id)->where('relation', '3')->leftJoin('user', 'messenger_friendship.user_two_id', '=', 'user.id')
                ->inRandomOrder()->limit(1)->select('user.username')->first();
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

        $badgeList = UserBadge::where('user_id', $args['userId'])->select('badge_id')->forPage($currentPage, 40)->get();

        $message = [
            'badgescode' => $badgeList
        ];

        return $this->jsonResponse($response, $message);
    }

    public function getID(Request $request, Response $response, array $args): Response
    {

        $cacheData = $this->cache->get(10);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        $profil = User::select('id', 'username', 'look', 'motto', 'online', 'limit_coins', 'vip_points', 'account_created', 'last_offline')->where('id', $args['id'])->first();

        if (!$profil) {
            throw new Exception('not-found', 404);
        }

        $stats = UserStats::where('id', $profil->id)->select('respect', 'achievement_score', 'online_time')->first();

        if (!$stats) {
            throw new Exception('not-found', 404);
        }

        $badgeCount = UserBadge::where('user_id', $profil->id)->count();

        $totalPage = 0;

        if ($badgeCount > 0) {
            $totalPage = ceil($badgeCount / 40);
        }

        $groupe = Groups::where('owner_id', $profil->id)->orderBy('id')->get();

        $countcoeur = MessengerFriendship::where('user_one_id', $profil->id)->where('relation', '1')->count();
        $countami   = MessengerFriendship::where('user_one_id', $profil->id)->where('relation', '2')->count();
        $countdead  = MessengerFriendship::where('user_one_id', $profil->id)->where('relation', '3')->count();

        $randomcoeur = null;
        $randomami = null;
        $randomdead = null;

        if ($countcoeur > 0) {
            $randomcoeur = MessengerFriendship::where('user_one_id', $profil->id)->where('relation', '1')->leftJoin('user', 'messenger_friendship.user_two_id', '=', 'user.id')
                ->inRandomOrder()->limit(1)->select('user.username')->first();
        }

        if ($countami > 0) {
            $randomami = MessengerFriendship::where('user_one_id', $profil->id)->where('relation', '2')->leftJoin('user', 'messenger_friendship.user_two_id', '=', 'user.id')
                ->inRandomOrder()->limit(1)->select('user.username')->first();
        }

        if ($countdead > 0) {
            $randomdead = MessengerFriendship::where('user_one_id', $profil->id)->where('relation', '3')->leftJoin('user', 'messenger_friendship.user_two_id', '=', 'user.id')
                ->inRandomOrder()->limit(1)->select('user.username')->first();
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
}
