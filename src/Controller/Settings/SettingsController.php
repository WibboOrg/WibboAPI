<?php
namespace App\Controller\Settings;

use App\Controller\DefaultController;
use App\Models\User;
use Exception;

class SettingsController extends DefaultController
{
    public function postPassword($request, $response)
    {
        $input = $request->getParsedBody();

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['oldpassword', 'newpassword', 'repassword']);

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('mail_valide', 'password')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if ($user->mail_valide == 0)
            throw new Exception('settings-password.mail-valid', 400);

        if (empty($data->oldpassword))
            throw new Exception('settings-password.empty-password', 400);

        if (hashMdp($data->oldpassword) != $user->password)
            throw new Exception('settings-password.empty-password', 400);

        if (empty($data->newpassword))
            throw new Exception('settings-password.empty-password', 400);

        if (strlen($data->newpassword) < 5)
            throw new Exception('settings-password.empty-password', 400);

        if (empty($data->repassword))
            throw new Exception('settings-password.empty-password', 400);

        if ($data->newpassword != $data->repassword) 
            throw new Exception('settings-password.same-password', 400);

        User::where('id', $userId)->update(['password' => hashMdp($data->newpassword)]);
        
        return $this->jsonResponse($response, null);
    }

    public function postGeneral($request, $response)
    {
        $input = $request->getParsedBody();

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['textamigo', 'online', 'join', 'troc']);

        $userId = $input['decoded']->sub;

        $textamigo = ($data->textamigo == "1") ? '0' : '1';
        $online = ($data->online == "1") ? '0' : '1';
        $join = ($data->join == "1") ? '0' : '1';
        $troc = ($data->troc == "1") ? '1' : '0';

        User::where('id', $userId)->update([
            'block_newfriends' => $textamigo,
            'hide_online' => $online,
            'hide_inroom' => $join,
            'accept_trading' => $troc,
        ]);

        return $this->jsonResponse($response, null);
    }
}
