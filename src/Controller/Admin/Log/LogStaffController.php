<?php
namespace App\Controller\Admin\Log;

use App\Controller\DefaultController;
use App\Models\StaffLog;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class LogStaffController extends DefaultController
{
    public function get(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $logs = StaffLog::orderBy('id', 'DESC')->limit(100)->get();
		
		$message = [
			'logs' => $logs
        ];

        return $this->jsonResponse($response, $message);
    }
}
