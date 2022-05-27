<?php
namespace App\Controller\Admin\Upload;

use App\Controller\DefaultController;
use App\Models\LogStaff;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class UploadBadgeController extends DefaultController
{
    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['code', 'title', 'desc']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $files = $request->getUploadedFiles();
        $badgeCode = $data->code;
        $badgeTitle = $data->title;
        $badgeDesc = $data->desc;

        if (empty($files['file']) || empty($badgeCode) || empty($badgeTitle)) {
            throw new Exception('error', 400);
        }

        $uploadFileName = $files['file']->getClientFilename();

        $size = getimagesize($files['file']->file);
        if ($size[1] > 41 || $size[0] > 41) {
            throw new Exception('error', 400);
        }

        $extension_upload = substr(strrchr($uploadFileName, '.'), 1);
        if ($extension_upload != 'gif') {
            throw new Exception('error', 400);
        }

        $badgeJson = array("badge_name_" . $badgeCode => $badgeTitle, "badge_desc_" . $badgeCode => $badgeDesc);

        $data = array(
            array(
                'action' => 'upload',
                'path' => 'c_images/album1584/' . $badgeCode . '.gif',
                'data' => base64_encode(file_get_contents($files['file']->file)),
            ),
            array(
                'action' => 'json',
                'path' => 'gamedata/BadgeTexts.json',
                'data' => json_encode($badgeJson),
            ),
        );

        $options = array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)));
        $context = stream_context_create($options);
        $result = file_get_contents('https://assets.wibbo.org/uploadApi.php?key=' . getenv('UPLOAD_API'), false, $context);
        if ($result === false || $result !== 'ok') {
            throw new Exception('error', 400);
        }

        LogStaff::insert([
            'pseudo' => $user->username,
            'action' => 'Upload du badge: ' . $badgeCode,
            'date' => time(),
        ]);

        return $this->jsonResponse($response, []);
    }
}
