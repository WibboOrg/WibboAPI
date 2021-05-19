<?php
namespace App\Controller\Admin\Upload;

use App\Controller\DefaultController;
use App\Models\User;
use Exception;

class UploadPageController extends DefaultController
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

        $link = 'custom/' . $userId . '_' . time();

        $data = array(
            array(
                'action' => 'upload',
                'path' => 'dcr/gamedata/wibbopages/' . $link,
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
			'link' => $link
        ];

        return $this->jsonResponse($response, $message);
    }
}