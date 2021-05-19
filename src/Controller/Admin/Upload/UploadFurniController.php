<?php
namespace App\Controller\Admin\Upload;

use App\Controller\DefaultController;
use App\Models\User;
use Exception;

class UploadFurniController extends DefaultController
{
    public function post($request, $response)
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
        $titre = $data->title;
        $desc = $data->desc;

        if (empty($files['file']) || empty($type) || empty($titre)) {
            throw new Exception('error', 400);
        }

        $uploadFileName = $files['file']->getClientFilename();

        $extension_upload = substr(strrchr($uploadFileName, '.'), 1);
        if ($extension_upload != 'swf') {
            throw new Exception('error', 400);
		}
		
        $codedumobis = str_replace('.swf', '', $uploadFileName);

        $nb_min = 10000000;
        $nb_max = 99999999;
        $nombre = mt_rand($nb_min, $nb_max);

        $sql = "";
        if ($type == '1') {
            $funi = '["s","' . $nombre . '","' . $codedumobis . '","0","0","1","1","","' . $titre . '","' . $desc . '","","-1","false","-1","false","-1","0","false","0","0","0"],';
            $sql .= "INSERT INTO catalog_items VALUES ('" . $nombre . "', '7529', '" . $nombre . "', '" . $codedumobis . "', '25', '0', '0', '1', '0', '0', '0', '');";
            $sql .= "INSERT INTO furniture VALUES ('" . $nombre . "', '" . $codedumobis . "', 's', '1', '1', '1', '0', '1', '0', '" . $nombre . "', '0', '1', '1', '1', '1', 'default', '1', '0', '0', '0', '0');";
        } else if ($type == '2') {
            $funi = '["i","' . $nombre . '","' . $codedumobis . '","0","0","0","0","","' . $titre . '","' . $desc . '","","-1","false","-1","false","-1","0","false","0","0","0"],';

            $sql .= "INSERT INTO catalog_items VALUES ('" . $nombre . "', '7529', '" . $nombre . "', '" . $codedumobis . "', '25', '0', '0', '1', '0', '0', '0', '');";
            $sql .= "INSERT INTO furniture VALUES ('" . $nombre . "', '" . $codedumobis . "', 'i', '1', '1', '1', '0', '0', '0', '" . $nombre . "', '0', '1', '1', '1', '1', 'default', '1', '0', '0', '0', '0');";
        } else {
            throw new Exception('Erreur', 400);
        }

        $product = '["' . $codedumobis . '","' . $titre . '","' . $desc . '"],';

        $data = array(
            array(
                'action' => 'add',
                'path' => 'dcr/gamedata/furnidata_fr.txt',
                'data' => $funi,
            ),
            array(
                'action' => 'add',
                'path' => 'dcr/gamedata/productdata_fr.txt',
                'data' => $product,
            ),
            array(
                'action' => 'upload',
                'path' => 'dcr/dcr/hof_furni2/' . $uploadFileName,
                'data' => base64_encode(file_get_contents($files['file']->file)),
            ),
        );

        $options = array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)));
        $context = stream_context_create($options);
        $result = file_get_contents('https://swf.wibbo.org/uploadApi.php?key=' . getenv('UPLOAD_API'), false, $context);
        if ($result === false || $result !== 'ok') {
            throw new Exception('error', 400);
        }
		
		$message = [
			'sql' => $sql
        ];

        return $this->jsonResponse($response, $message);
    }
}