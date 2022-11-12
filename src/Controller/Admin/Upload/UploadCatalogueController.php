<?php
namespace App\Controller\Admin\Upload;

use App\Controller\DefaultController;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;
use App\Helper\Utils;

class UploadCatalogueController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 11) {
            throw new Exception('permission', 403);
        }

        $files = $request->getUploadedFiles();

        if (empty($files['file'])) {
            throw new Exception('error', 400);
        }

        $uploadFileName = $files['file']->getClientFilename();

        $extension_upload = substr(strrchr($uploadFileName, '.'), 1);
        if ($extension_upload != 'png') {
            throw new Exception('error', 400);
        }

        $data = array(
            array(
                'action' => 'upload',
                'path' => 'c_images/catalogue/' . $uploadFileName,
                'data' => base64_encode(file_get_contents($files['file']->file)),
            ),
        );

        if (!Utils::uploadApi("assets", $data)) {
            throw new Exception('error', 400);
        }

        return $this->jsonResponse($response, []);
    }
}