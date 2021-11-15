<?php
namespace App\Controller\Utils;

use App\Controller\DefaultController;
use App\Models\Room;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class RoomController extends DefaultController
{
    public function get(Request $request, Response $response, array $args): Response
    {
        if (empty($args['roomId']) || !is_numeric($args['roomId'])) {
            throw new Exception('not-found', 404);
        }

        $roomId = $args['roomId'];

        $room = Room::select('caption', 'owner', 'description')->where('id', $roomId)->first();

        if (!$room) {
            throw new Exception('not-found', 404);
        }

        $message = [
            'room' => $room,
        ];

        return $this->jsonResponse($response, $message);
    }
}
