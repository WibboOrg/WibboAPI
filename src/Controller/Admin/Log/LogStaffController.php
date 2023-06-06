<?php
namespace App\Controller\Admin\Log;

use App\Controller\DefaultController;
use App\Models\LogStaff;
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

        $limitPage = 100;
        $total = LogStaff::count();

        $totalPage = ceil($total / $limitPage);
        if (!empty($_GET['page']) && is_numeric($_GET['page'])) {
            $currentPage = intval($_GET['page']);

            if ($currentPage > $totalPage) {
                $currentPage = $totalPage;
            }
        } else {
            $currentPage = 1;
        }

        $logs = LogStaff::orderBy('id', 'DESC')->forPage($currentPage, $limitPage)->get();
		
		$message = [
            'totalPage' => $totalPage,
			'logs' => $logs
        ];

        return $this->jsonResponse($response, $message);
    }
}
