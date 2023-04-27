<?php
namespace App\Controller\Shop;

use App\Controller\DefaultController;
use App\Models\LogShop;
use App\Models\User;
use App\Models\UserStats;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Helper\Utils;
use Exception;

class ShopController extends DefaultController
{
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

            User::where('id', $userId)->increment('limit_coins', $virtual_currency);

            LogShop::insert([
                'userid' => $userId,
                'date' => time(),
                'prix' => $virtual_currency,
                'achat' => 'Achat de ' . $virtual_currency . ' LTC (Dedi)',
                'type' => '1',
            ]);

            UserStats::where('id', $userId)->increment('achievement_score', $virtual_currency);

            Utils::sendMusCommand('updateltc', $userId . chr(1) . $virtual_currency);
            Utils::sendMusCommand('addwinwin', $userId . chr(1) . $virtual_currency);
        }

        return $this->jsonResponse($response, []);
    }
}