<?php
namespace App\Controller\Admin\Log;

use App\Controller\DefaultController;
use App\Models\CmdLogs;
use App\Models\User;
use Exception;

class LogCommandController extends DefaultController
{
    public function get($request, $response)
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $limitPage = 100;
        $total = CmdLogs::count();

        $totalPage = ceil($total / $limitPage);

        if (!empty($_GET['page']) && is_numeric($_GET['page'])) {
            $currentPage = intval($_GET['page']);

            if ($currentPage > $totalPage) {
                $currentPage = $totalPage;
            }
        } else {
            $currentPage = 1;
        }

        $cmdlogs = CmdLogs::orderBy('timestamp', 'DESC')->forPage($currentPage, $limitPage)->get();

		$message = [
            'totalPage' => $totalPage,
			'cmdlogs' => $cmdlogs
        ];

        return $this->jsonResponse($response, $message);
    }

    public function post($request, $response)
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['username', 'startdate', 'enddate']);

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $username = $data->username;
        $startdate = $data->startdate;
        $enddate = $data->enddate;

        if (empty($username) || empty($startdate) || !strtotime($startdate) || empty($enddate) || !strtotime($enddate)) {
            throw new Exception('error', 400);
        }

        $timestamp = strtotime($startdate);
        $timestampEnd = strtotime($enddate);

        $cmd = CmdLogs::where('user_name', $username)->where('timestamp', '>', $timestamp)->where('timestamp', '<', $timestampEnd)->orderBy('timestamp', 'DESC')->limit(100)->get();
	
		$message = [
			'cmd' => $cmd
        ];

        return $this->jsonResponse($response, $message);
    }
}