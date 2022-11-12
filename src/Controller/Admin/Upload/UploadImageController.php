<?php
namespace App\Controller\Admin\Upload;

use App\Controller\DefaultController;
use App\Models\User;
use App\Models\LogStaff;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;
use App\Helper\Utils;

class UploadImageController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 3) {
            throw new Exception('permission', 403);
        }

        $files = $request->getUploadedFiles();

        if (empty($files['file'])) {
            throw new Exception('error', 400);
        }

        $uploadFileName = $files['file']->getClientFilename();

        $extension_upload = substr(strrchr($uploadFileName, '.'), 1);
        if ($extension_upload != 'png' && $extension_upload != 'gif' && $extension_upload != 'jpeg') {
            throw new Exception('error', 400);
        }

        $newFileName = time() . '.' . $extension_upload;

        $data = array(
            array(
                'action' => 'upload',
                'path' => 'uploads/' . $newFileName,
                'data' => base64_encode(file_get_contents($files['file']->file)),
            ),
        );

        if (!Utils::uploadApi("cdn", $data)) {
            throw new Exception('error', 400);
        }

        $message = [
			'url' => '//cdn.wibbo.org/uploads/' . $newFileName
        ];

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Ajout de l\'image: ' . $newFileName,
            'date' => time()
        ]);

        return $this->jsonResponse($response, $message);
    }
}