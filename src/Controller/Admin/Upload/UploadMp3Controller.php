<?php
namespace App\Controller\Admin\Upload;

use App\Controller\DefaultController;
use App\Models\LogStaff;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;
use App\Helper\Utils;

class UploadMp3Controller extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $files = $request->getUploadedFiles();

        if (empty($files['file'])) {
            throw new Exception('error', 400);
        }

        $uploadFileName = $files['file']->getClientFilename();

        if (!preg_match('/^[a-z0-9-]+\.mp3$/', $uploadFileName)) {
            throw new Exception('error', 400);
        }

        $extensionUpload = substr(strrchr($uploadFileName, '.'), 1);
        if ($extensionUpload != 'mp3') {
            throw new Exception('error', 400);
        }

        $data = array(
            array(
                'action' => 'upload',
                'path' => 'sounds/' . $uploadFileName,
                'data' => base64_encode(file_get_contents($files['file']->file)),
            ),
        );

        if (!Utils::uploadApi("cdn", $data)) {
            throw new Exception('error', 400);
        }

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Ajout de mp3: ' . $uploadFileName,
            'date' => time()
        ]);

        return $this->jsonResponse($response, []);
    }
}