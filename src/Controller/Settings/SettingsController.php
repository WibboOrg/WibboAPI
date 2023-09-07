<?php
namespace App\Controller\Settings;

use App\Controller\DefaultController;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Helper\Utils;

use Exception;

class SettingsController extends DefaultController
{
    public function postPassword(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['oldpassword', 'newpassword', 'repassword']);

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('mail', 'password')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if (empty($user->mail))
            throw new Exception('settings-password.mail-valid', 400);

        if (empty($data->oldpassword))
            throw new Exception('settings-password.empty-password', 400);

        if (Utils::hashMdp($data->oldpassword) != $user->password)
            throw new Exception('settings-password.empty-password', 400);

        if (empty($data->newpassword))
            throw new Exception('settings-password.empty-password', 400);

        if (strlen($data->newpassword) < 5)
            throw new Exception('settings-password.empty-password', 400);

        if (empty($data->repassword))
            throw new Exception('settings-password.empty-password', 400);

        if ($data->newpassword != $data->repassword) 
            throw new Exception('settings-password.same-password', 400);

        User::where('id', $userId)->update(['password' => Utils::hashMdp($data->newpassword)]);
        
        return $this->jsonResponse($response, []);
    }

    public function postGeneral(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['textamigo', 'online', 'join', 'troc']);

        $userId = $input['decoded']->sub;

        $textamigo = ($data->textamigo == 1) ? 0 : 1;
        $online = ($data->online == 1) ? 0 : 1;
        $join = ($data->join == 1) ? 0 : 1;
        $troc = ($data->troc == 1) ? 1 : 0;

        User::where('id', $userId)->update([
            'block_newfriends' => $textamigo,
            'hide_online' => $online,
            'hide_inroom' => $join,
            'accept_trading' => $troc,
        ]);

        return $this->jsonResponse($response, []);
    }
}
