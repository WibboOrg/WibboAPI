<?php
namespace App\Controller\Ranking;

use App\Controller\DefaultController;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\User;

class RankingController extends DefaultController
{   
    private int $minimalTime = 31 * 24 * 60 * 60;

    public function getClassement(Request $request, Response $response, array $args): Response
    {
        $cacheData = $this->cache->get(5);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);
        
        $winwin = User::join('user_stats', 'user.id', '=', 'user_stats.id')->where('user.last_online', '>', time() - $this->minimalTime)->where('user.is_banned', '0')->orderBy('user_stats.achievement_score', 'DESC')->limit(20)
                    ->select('user.id', 'user.username', 'user.look', 'user_stats.achievement_score')->get();
        $jetons = User::orderBy('jetons', 'DESC')->where('user.last_online', '>', time() - $this->minimalTime)->where('user.is_banned', '0')->limit(20)->select('id', 'username', 'look', 'jetons')->get();
        $wibbopoint = User::orderBy('vip_points', 'DESC')->where('user.last_online', '>', time() - $this->minimalTime)->where('user.is_banned', '0')->limit(20)->select('id', 'username', 'look', 'vip_points')->get();

        $message = [
            'winwin' => $winwin,
            'wibbopoint' => $wibbopoint,
            'jetons' => $jetons
        ];

        $this->cache->save($message);

        return $this->jsonResponse($response, $message);
    }

    public function getInfluences(Request $request, Response $response, array $args): Response
    {
        $cacheData = $this->cache->get(5);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        time() - $this->minimalTime = time() + 31 * 24 * 60 * 60;

        $respects = User::join('user_stats', 'user.id', '=', 'user_stats.id')->where('user.is_banned', '0')->orderBy('user_stats.respect', 'DESC')->limit(20)
                        ->select('user.id', 'user.username', 'user.look', 'user_stats.respect')->get();
        $connexions = User::join('user_stats', 'user.id', '=', 'user_stats.id')->where('user.is_banned', '0')->where('user.rank', '<', '13')->orderBy('user_stats.online_time', 'DESC')->limit(20)
                        ->select('user.id', 'user.username', 'user.look', 'user_stats.online_time')->get();
        $moisvip = User::orderBy('mois_vip', 'DESC')->where('user.is_banned', '0')->limit(20)
                        ->select('id', 'username', 'look', 'mois_vip')->get();

        $message = [
            'respects' => $respects,
            'connexions' => $connexions,
            'moisvip' => $moisvip
        ];

        $this->cache->save($message);

        return $this->jsonResponse($response, $message);
    }

    public function getTop(Request $request, Response $response, array $args): Response
    {
        $cacheData = $this->cache->get(5);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        $month = User::where('game_points_month', '>', '0')->where('user.is_banned', '0')->where('rank', '<', '6')->orderBy('game_points_month', 'DESC')->limit(10)->select('user.id', 'username', 'look', 'game_points_month')->get();
        $top = User::where('game_points', '>', '0')->where('user.last_online', '>', time() - $this->minimalTime)->where('user.is_banned', '0')->orderBy('game_points', 'DESC')->limit(5)->select('id', 'username', 'look', 'game_points')->get();

        $message = [
            'top' => $month,
            'best' => $top
        ];

        $this->cache->save($message);

        return $this->jsonResponse($response, $message);
    }

    public function getTopRun(Request $request, Response $response, array $args): Response
    {
        $cacheData = $this->cache->get(5);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        $month = User::where('run_points_month', '>', '0')->where('user.is_banned', '0')->where('rank', '<', '6')->orderBy('run_points_month', 'DESC')->limit(10)->select('user.id', 'username', 'look', 'run_points_month')->get();
        $top = User::where('run_points', '>', '0')->where('user.last_online', '>', time() - $this->minimalTime)->where('user.is_banned', '0')->orderBy('run_points', 'DESC')->limit(5)->select('id', 'username', 'look', 'run_points')->get();

        $message = [
            'top' => $month,
            'best' => $top
        ];

        $this->cache->save($message);

        return $this->jsonResponse($response, $message);
    }

    public function getTopMazo(Request $request, Response $response, array $args): Response
    {
        $cacheData = $this->cache->get(5);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        $top = User::where('mazo', '>', '0')->where('user.is_banned', '0')->where('rank', '<', '6')->orderBy('mazo', 'DESC')->limit(10)->select('id', 'username', 'look', 'mazo')->get();
        $best = User::where('mazoscore', '>', '0')->where('user.last_online', '>', time() - $this->minimalTime)->where('user.is_banned', '0')->orderBy('mazoscore', 'DESC')->limit(5)->select('id', 'username', 'look', 'mazoscore')->get();

        $message = [
            'top' => $top,
            'best' => $best
        ];

        $this->cache->save($message);

        return $this->jsonResponse($response, $message);
    }
}