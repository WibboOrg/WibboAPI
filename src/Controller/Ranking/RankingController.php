<?php
namespace App\Controller\Ranking;

use App\Controller\DefaultController;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\User;

class RankingController extends DefaultController
{   
    public function getClassement(Request $request, Response $response, array $args): Response
    {
        $cacheData = $this->cache->get(5);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);
        
        $winwin = User::join('user_stats', 'users.id', '=', 'user_stats.id')->orderBy('user_stats.achievement_score', 'DESC')->limit(20)->select('users.id', 'users.username', 'users.look', 'user_stats.achievement_score')->get();
        $jetons = User::orderBy('jetons', 'DESC')->limit(20)->select('id', 'username', 'look', 'jetons')->get();
        $wibbopoint = User::orderBy('vip_points', 'DESC')->limit(20)->select('id', 'username', 'look', 'vip_points')->get();

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

        $respects = User::join('user_stats', 'users.id', '=', 'user_stats.id')->orderBy('user_stats.respect', 'DESC')->limit(20)
                        ->select('users.id', 'users.username', 'users.look', 'user_stats.respect')->get();
        $connexions = User::join('user_stats', 'users.id', '=', 'user_stats.id')->where('users.rank', '<', '13')->orderBy('user_stats.online_time', 'DESC')->limit(20)
                        ->select('users.id', 'users.username', 'users.look', 'user_stats.online_time')->get();
        $moisvip = User::orderBy('mois_vip', 'DESC')->limit(20)->select('id', 'username', 'look', 'mois_vip')->get();

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

        $month = User::where('game_points_month', '>', '0')->orderBy('game_points_month', 'DESC')->limit(10)->select('users.id', 'username', 'look', 'game_points_month')->get();
        $top = User::where('game_points', '>', '0')->orderBy('game_points', 'DESC')->limit(5)->select('id', 'username', 'look', 'game_points')->get();

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

        $month = User::where('run_points_month', '>', '0')->orderBy('run_points_month', 'DESC')->limit(10)->select('users.id', 'username', 'look', 'run_points_month')->get();
        $top = User::where('run_points', '>', '0')->orderBy('run_points', 'DESC')->limit(5)->select('id', 'username', 'look', 'run_points')->get();

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

        $top = User::where('mazo', '>', '0')->where('rank', '<', '6')->orderBy('mazo', 'DESC')->limit(10)->select('id', 'username', 'look', 'mazo')->get();
        $best = User::where('mazoscore', '>', '0')->orderBy('mazoscore', 'DESC')->limit(5)->select('id', 'username', 'look', 'mazoscore')->get();

        $message = [
            'top' => $top,
            'best' => $best
        ];

        $this->cache->save($message);

        return $this->jsonResponse($response, $message);
    }
}