<?php
namespace App\Controller\CatalogItem;

use App\Controller\DefaultController;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\CatalogItem;
use Exception;

class RareController extends DefaultController
{
    public function get(Request $request, Response $response, array $args): Response
    {
        $rares = CatalogItem::select('catalog_item.id', 'catalog_item.catalog_name', 'item_stat.amount', 'item_base.rarity_level')
        ->leftJoin('item_stat', 'item_stat.base_id', 'catalog_item.item_id')
        ->leftJoin('item_base', 'item_base.id', 'catalog_item.item_id')
        ->where('item_stat.amount', '>=', 0)
        ->where('catalog_item.catalog_name', 'like', 'wibboloot%')
        ->get();

        $message = [
            'rares' => $rares
        ];

        return $this->jsonResponse($response, $message);
    }
}
