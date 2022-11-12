<?php
namespace App\Controller\Admin\Upload;

use App\Controller\DefaultController;
use App\Helper\Utils;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class UploadPageController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
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
                'path' => 'wibbopages/' . $link,
                'data' => base64_encode(file_get_contents($files['file']->file)),
            ),
        );

        if (!Utils::uploadApi("assets", $data)) {
            throw new Exception('error', 400);
        }

		$message = [
			'link' => $link
        ];

        return $this->jsonResponse($response, $message);
    }
}