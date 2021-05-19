<?php
namespace App\Controller\Utils;

use App\Controller\DefaultController;
use App\Models\User;
use Exception;

class UtilController extends DefaultController
{
    public function getAvatarUrl($request, $response, $args)
    {
        $user = User::where('username', $args['username'])->select('look')->first();

        if (!$user) {
            throw new Exception('not-found', 404);
        }

        $options = "";
        foreach ($_GET as $key => $value) {
            $options .= '&'.$key.'='.$value;
        }

        return $response->withRedirect("https://cdn.wibbo.org/habbo-imaging/avatarimage?figure=". $user->look.$options);
    }

    public function getSearchUser($request, $response, $args)
    {
        $users = [];

        $filter = preg_replace("/[^a-z\d\-=\?!@:\.]/i", "", $args['username']);
        if ($filter == $args['username'])
            $users = User::where('username', 'LIKE', str_replace(array('%', '_'), array('\%', '\_'), $args['username']) . '%')->select('username', 'look')->limit(5)->get();

        $message = [
            'users' => $users
        ];
      
        return $this->jsonResponse($response, $message);
    }
}