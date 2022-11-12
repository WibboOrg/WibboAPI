<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\NavigatorPublic;
use App\Models\LogStaff;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;
use App\Helper\Utils;

class NavigatorController extends DefaultController
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

        $nav = NavigatorPublic::where('enabled', '=', '1')->orderBy('order_num', 'DESC')->get();

        $message = [
			'nav' => $nav
        ];

        return $this->jsonResponse($response, $message);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $id = $args["id"];

        if (empty($id) || !is_numeric($id)) {
            throw new Exception('error', 400);
        }

        $nav = NavigatorPublic::where('room_id', $id)->first();
        if (!$nav) {
            throw new Exception('error', 400);
        }

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Supression d\'un appart au navigateur: ' . $id,
            'date' => time(),
		]);
		
        NavigatorPublic::where('room_id', $id)->delete();

        return $this->jsonResponse($response, []);
    }
    
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
		$userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['roomid', 'category']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
		if(!$user) throw new Exception('disconnect', 401);
		
        if ($user->rank < 8) {
			throw new Exception('permission', 403);
        }

        $files = $request->getUploadedFiles();
        $roomid = $data->roomid;
        $categorie = $data->category;

        if (!is_numeric($roomid) || !isset($categorie)) {
            throw new Exception('error', 400);
        }

        if (empty($files['file'])) {
            throw new Exception('error', 400);
        }

        $uploadFileName = $files['file']->getClientFilename();

        $size = getimagesize($files['file']->file);
        if ($size[0] != 110 || $size[1] != 110) {
            throw new Exception('error', 400);
        }

        $extension_upload = substr(strrchr($uploadFileName, '.'), 1);
        if ($extension_upload != 'png') {
            throw new Exception('error', 400);
        }

        $filePath = "navigator/room_" . $roomid . ".png";

        $data = array(
			array(
				'action' => 'upload',
				'path' => 'c_images/' . $filePath,
				'data' => base64_encode(file_get_contents($files['file']->file))
			)
		);
	
		if (!Utils::uploadApi("assets", $data)) {
			throw new Exception('error', 400);
		}

        NavigatorPublic::insert([
            'room_id' => $roomid,
            'image_url' => $filePath,
            'category_type' => $categorie,
            'enabled' => '1',
        ]);

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Ajoute d\'un appart au navigateur: ' . $roomid,
            'date' => time(),
        ]);

        return $this->jsonResponse($response, []);
    }
}