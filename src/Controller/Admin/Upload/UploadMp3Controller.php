<?php
namespace App\Controller\Admin\Upload;

use App\Controller\DefaultController;
use App\Models\User;
use Exception;

class UploadMp3Controller extends DefaultController
{
    public function post($request, $response)
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
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

        $extension_upload = substr(strrchr($uploadFileName, '.'), 1);
        if ($extension_upload != 'mp3') {
            throw new Exception('error', 400);
        }

        $data = array(
            array(
                'action' => 'upload',
                'path' => 'sounds/' . $uploadFileName,
                'data' => base64_encode(file_get_contents($files['file']->file)),
            ),
        );

        $options = array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)));
        $context = stream_context_create($options);
        $result = file_get_contents('https://cdn.wibbo.org/uploadApi.php?key=' . getenv('UPLOAD_API'), false, $context);
        if ($result === false || $result !== 'ok') {
            throw new Exception('error', 400);
        }
    }
}