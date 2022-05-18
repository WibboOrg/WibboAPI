<?php
namespace App\Controller\Admin\Upload;

use App\Controller\DefaultController;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class UploadFurniController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['type', 'title', 'desc']);

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 12) {
            throw new Exception('permission', 403);
        }

        $files = $request->getUploadedFiles();
        $type = $data->type;
        $furniTitle = $data->title;
        $furniDesc = $data->desc;

        if (empty($files['file']) || empty($type) || empty($furniTitle)) {
            throw new Exception('error', 400);
        }

        $uploadFileName = $files['file']->getClientFilename();

        $extension_upload = substr(strrchr($uploadFileName, '.'), 1);
        if ($extension_upload != 'nitro') {
            throw new Exception('error', 400);
		}
		
        $furniName = str_replace('.nitro', '', $uploadFileName);

        $nb_min = 10000000;
        $nb_max = 99999999;
        $furniId = mt_rand($nb_min, $nb_max);

        $sql = "";
        if ($type == '1') {
            $sql .= "INSERT INTO catalog_item VALUES ('" . $furniId . "', '7529', '" . $furniId . "', '" . $furniName . "', '25', '0', '0', '0', '1', '0', '0', '0', '');";
            $sql .= "INSERT INTO item_base VALUES ('" . $furniId . "', '" . $furniName . "', 's', '1', '1', '1', '0', '1', '0', '" . $furniId . "', '0', '1', '1', '1', '1', 'default', '1', '0', '0', '0', '0');";
        } else if ($type == '2') {
            $sql .= "INSERT INTO catalog_item VALUES ('" . $furniId . "', '7529', '" . $furniId . "', '" . $furniName . "', '25', '0', '0', '0', '1', '0', '0', '0', '');";
            $sql .= "INSERT INTO item_base VALUES ('" . $furniId . "', '" . $furniName . "', 'i', '1', '1', '1', '0', '0', '0', '" . $furniId . "', '0', '1', '1', '1', '1', 'default', '1', '0', '0', '0', '0');";
        } else {
            throw new Exception('Erreur', 400);
        }

        $funidataCode = array(
            "id" => intval($furniId),
            "classname" => $furniName,
            "revision" => 0,
            "category" => "",
            "name" => utf8_decode($furniTitle),
            "description" => utf8_decode($furniDesc),
            "adurl" >= "",
            "offerid" => 0,
            "buyout" => false,
            "rentofferid" => 0,
            "rentbuyout" =>  false,
            "customparams" => "",
            "specialtype" => 0,
            "bc" => false,
            "excludeddynamic" => false,
            "furniline" => "",
            "environment" => "",
            "rare" => false
        );
        
        if ($type == 's') {
            $funidataCode = array_merge($funidataCode, array(
                "defaultdir" => "0",
                "xdim" => intval(0),
                "ydim" => intval(0),
                "partcolors" => array(),
                "canstandon" => false,
                "cansiton" => false,
                "canlayon" => false
            ));
        }
       
        if($type == 's') $furnidata["roomitemtypes"]["furnitype"][] = $funidataCode;
        else $furnidata["wallitemtypes"]["furnitype"][] = $funidataCode;

        $productCode = array();
        $productCode[0] = array('code' => $furniName, 'name' => $furniTitle, 'description' => $furniDesc);

        $product = array(
            "productdata" => array(
                "product" => $productCode
            )
        );

        $data = array(
            array(
                'action' => 'json',
                'path' => 'gamedata/FurnitureData.json',
                'data' => json_encode($furnidata),
            ),
            array(
                'action' => 'json',
                'path' => 'gamedata/ProductData.json',
                'data' => json_encode($product),
            ),
            array(
                'action' => 'upload',
                'path' => 'bundled/furniture/' . $uploadFileName,
                'data' => base64_encode(file_get_contents($files['file']->file)),
            ),
        );

        $options = array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)));
        $context = stream_context_create($options);
        $result = file_get_contents('https://assets.wibbo.org/uploadApi.php?key=' . getenv('UPLOAD_API'), false, $context);
        if ($result === false || $result !== 'ok') {
            throw new Exception('error', 400);
        }
		
		$message = [
			'sql' => $sql
        ];

        return $this->jsonResponse($response, $message);
    }
}