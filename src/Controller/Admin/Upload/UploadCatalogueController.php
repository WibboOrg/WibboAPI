<?php
namespace App\Controller\Admin\Upload;

use App\Controller\DefaultController;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class UploadCatalogueController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 12) {
            throw new Exception('permission', 403);
        }

        $files = $request->getUploadedFiles();

        if (empty($files['file'])) {
            throw new Exception('error', 400);
        }

        $uploadFileName = $files['file']->getClientFilename();

        $extension_upload = substr(strrchr($uploadFileName, '.'), 1);
        if ($extension_upload != 'png') {
            throw new Exception('L\'image doit être au format PNG', 400);
        }

        $data = array(
            array(
                'action' => 'upload',
                'path' => 'dcr/c_images/catalogue/' . $uploadFileName,
                'data' => base64_encode(file_get_contents($files['file']->file)),
            ),
        );

        $options = array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)));
        $context = stream_context_create($options);
        $result = file_get_contents('https://swf.wibbo.org/uploadApi.php?key=' . getenv('UPLOAD_API'), false, $context);
        if ($result === false || $result !== 'ok') {
            throw new Exception('error', 400);
        }

        return $this->jsonResponse($response, []);
    }
}