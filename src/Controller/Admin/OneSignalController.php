<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\User;
use Exception;

class OneSignalController extends DefaultController
{
    public function get(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['title', 'content', 'url']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 12) {
            throw new Exception('permission', 403);
        }

        if (empty($data->title) || empty($data->content) || empty($data->url)) {
            throw new Exception('error', 400);
        }

        $heading = array(
            "en" => $data->title,
        );

        $content = array(
            "en" => $data->content,
        );

        $hashes_array = array();
        array_push($hashes_array, array(
            "id" => "link-button",
            "text" => "Voir",
            "url" => $data->url,
        ));

        $fields = array(
            'app_id' => "49a51879-7d7d-4253-b51c-a074eb06bfc6",
            'included_segments' => array(
                'Subscribed Users',
            ),
            'headings' => $heading,
            'contents' => $content,
            'web_buttons' => $hashes_array,
        );

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic NGEwMGZmMjItY2NkNy0xMWUzLTk5ZDUtMDAwYzI5NDBlNjJj',
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result, true);

        $message = [$data];

        return $this->jsonResponse($response, $message);
    }
}
