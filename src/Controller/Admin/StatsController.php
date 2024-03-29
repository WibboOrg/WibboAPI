<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\EmulatorStats;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class StatsController extends DefaultController
{
    public function get(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 6) {
            throw new Exception('permission', 403);
        }

        $startTime = time() - 24 * 60 * 60;
        $statsNow = EmulatorStats::where('time', '>', $startTime)->orderBy('time', 'ASC')->get();

        $firstTime = time() - (8 * 24 * 60 * 60);
        $lastTime = time() - (7 * 24 * 60 * 60);
        $statsLastWeek = EmulatorStats::where('time', '>', $firstTime)->where('time', '<', $lastTime)->orderBy('time', 'ASC')->get();
		
		$message = [
			'now' => $statsNow,
			'lastweek' => $statsLastWeek
        ];

        return $this->jsonResponse($response, $message);
    }
}
