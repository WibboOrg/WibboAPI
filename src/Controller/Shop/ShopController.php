<?php
namespace App\Controller\Shop;

use App\Controller\DefaultController;
use App\Models\BoutiqueLog;
use App\Models\User;
use App\Models\UserBadges;
use App\Models\UserStats;
use App\Models\UserVip;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Helper\Utils;
use Exception;

class ShopController extends DefaultController
{
    public function buyWibboPoint(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['count']);

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('jetons')->first();

        if(!$user) throw new Exception('disconnect', 401);

        $nombrePoints = floor(intval($data->count));
        if (!is_numeric($nombrePoints) || $nombrePoints < 50 || $user->jetons < $nombrePoints) 
            throw new Exception('shop.jetons-missing', 400);

        BoutiqueLog::insert([
            'userid' => $userId,
            'date' => time(),
            'prix' => $nombrePoints,
            'achat' => 'Achat de ' . $nombrePoints . ' Points',
            'type' => '5',
        ]);

        User::where('id', $userId)->decrement('jetons', $nombrePoints);
        User::where('id', $userId)->increment('vip_points', $nombrePoints);

        UserStats::where('id', $userId)->increment('achievement_score', $nombrePoints);

        Utils::sendMusCommand('updatepoints', $userId . chr(1) . $nombrePoints);
        Utils::sendMusCommand('addwinwin', $userId . chr(1) . $nombrePoints);

        return $this->jsonResponse($response, []);
    }

    public function verifDedipass(Request $request, Response $response, array $args): Response
    {
        $status = !empty($_POST['status']) ? preg_replace('/[^a-zA-Z0-9]+/', '', $_POST['status']) : '';
        $code = !empty($_POST['code']) ? preg_replace('/[^a-zA-Z0-9]+/', '', $_POST['code']) : '';
        $rate = !empty($_POST['rate']) ? preg_replace('/[^a-zA-Z0-9\-]+/', '', $_POST['rate']) : '';
        $payout = !empty($_POST['payout']) ? (float) $_POST['payout'] : '';
        $privateKey = !empty($_POST['privateKey']) ? preg_replace('/[^a-zA-Z0-9]+/', '', $_POST['privateKey']) : '';
        $virtual_currency = !empty($_POST['virtual_currency']) ? preg_replace('/[^a-zA-Z0-9]+/', '', $_POST['virtual_currency']) : '';

        // Paramètre renseigné lors de l'affichage du module de paiement
        $custom = !empty($_POST['custom']) ? preg_replace('/[^a-zA-Z0-9\-\_]+/', '', $_POST['custom']) : '';

        if($status == 'success' && $privateKey == getenv('DEDIPASS')) {
        // La transaction est validée et payée.
        // Vous pouvez utiliser la variable $custom et $virtual_currency
        // pour traiter la transaction.
            if (!is_numeric($custom))
                throw new Exception('shop.buy-error', 400);
            
            $userId = $custom;

            $user = User::where('id', $userId)->select('id')->first();

            if(!$user)
                throw new Exception('shop.buy-error');

            User::where('id', $userId)->increment('jetons', $virtual_currency);

            BoutiqueLog::insert([
                'userid' => $userId,
                'date' => time(),
                'prix' => $virtual_currency,
                'achat' => 'Achat de ' . $virtual_currency . ' Jetons (Dedi)',
                'type' => '1',
            ]);

            UserStats::where('id', $userId)->increment('achievement_score', $virtual_currency);

            Utils::sendMusCommand('addwinwin', $userId . chr(1) . $virtual_currency);
        }

        return $this->jsonResponse($response, []);
    }

    public function buyPremium(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('jetons', 'rank')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if ($user->jetons < 200)
            throw new Exception('shop.jetons-missing', 400);

        if ($user->rank > 1)
            throw new Exception('error', 400);

        $date = time();
        $mois = time() + (60 * 60 * 24 * 31);

        BoutiqueLog::insert([
            'userid' => $userId,
            'date' => time(),
            'prix' => '200',
            'achat' => 'Achat d\'un mois au Premium Club',
            'type' => '6',
        ]);

        UserVip::insert([
            'user_id' => $userId,
            'subscription_id' => 'wibbo_vip',
            'timestamp_activated' => $date,
            'timestamp_expire' => $mois,
        ]);

        User::where('id', $userId)->update(['rank' => '2']);
        User::where('id', $userId)->decrement('jetons', 200);
        User::where('id', $userId)->increment('credits', 10000000);
        User::where('id', $userId)->increment('mois_vip');

        UserBadges::insert([
            'user_id' => $userId,
            'badge_id' => 'WPREMIUM',
        ]);

        UserStats::where('id', $userId)->increment('achievement_score', 200);

        Utils::sendMusCommand('updatecredits', $userId);
        Utils::sendMusCommand('addwinwin', $userId . chr(1) . '200');

        return $this->jsonResponse($response, []);
    }

    public function buyBadgeperso(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('jetons', 'look', 'username')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if ($user->jetons < 100)
            throw new Exception('shop.jetons-missing', 400);

        $badgecode = "perso_" . $userId . "_" . rand(0, 9999999);

        $badge = UserBadges::where('user_id', $userId)->where('badge_id', $badgecode)->first();
        if ($badge)
            throw new Exception('error', 400);

        $avatarImg = Utils::getSslPage("https://cdn.wibbo.org/habbo-imaging/avatarimage?figure=" . $user->look . "&gesture=sml&head_direction=3");

        if(!!empty($avatarImg))
            throw new Exception('error', 400);

        $im = imagecreate(40, 40);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagecolortransparent($im, $white);
        $home = imagecreatefromstring($avatarImg);
        imagecopy($im, $home, -13, -19, 0, 0, 64, 110);

        //Create Image
        ob_start(); 
        imagegif($im);
        $image_data = ob_get_contents(); 
        ob_end_clean(); 

        $uploadBadge = array(
            'action' => 'upload',
            'path' => 'dcr/c_images/album1584/' . $badgecode . '.gif',
            'data' => base64_encode($image_data)
        );

        $putText = array(
            'action' => 'add',
            'path' => "dcr/gamedata/texts_fr.txt",
            'data' => "\nbadge_name_" . $badgecode . "=Badge de " . $user->username
        );

        $data = array($uploadBadge, $putText);

        $options = array('http' => array('header'  => "Content-type: application/x-www-form-urlencoded\r\n", 'method'  => 'POST', 'content' => http_build_query($data)));
        $context  = stream_context_create($options);
        $result = file_get_contents('https://swf.wibbo.org/uploadApi.php?key=' . getenv('UPLOAD_API'), false, $context);
        if ($result === FALSE || $result !== 'ok')
            throw new Exception('error', 400);

        UserBadges::insert([
            'user_id' => $userId,
            'badge_id' => $badgecode,
            'badge_slot' => '0',
        ]);

        BoutiqueLog::insert([
            'userid' => $userId,
            'date' => time(),
            'prix' => 100,
            'achat' => 'Achat badge perso ' . $badgecode,
            'type' => '12',
        ]);

        User::where('id', $userId)->decrement('jetons', 100);
        UserStats::where('id', $userId)->increment('achievement_score', 100);

        Utils::sendMusCommand('addwinwin', $userId . chr(1) . '100');

        return $this->jsonResponse($response, []);
    }

}