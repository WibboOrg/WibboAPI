<?php 
namespace App\Controller\Map;

use App\Controller\DefaultController;
use App\Models\News;
use App\Models\ForumThread;
use Slim\Http\Request;
use Slim\Http\Response;

class MapController extends DefaultController
{
    public function get(Request $request, Response $response, array $args): Response
    {
        $lastNews = News::select('id', 'link_keyword', 'topstory_image', 'title', 'snippet', 'timestamp')->orderBy('timestamp', 'DESC')->get();
        $posts = ForumThread::select('id', 'lastpost_date')->orderBy('lastpost_date', 'DESC')->limit(50000)->get();

        $message = [
            'lastNews' => $lastNews,
            'posts' => $posts
        ];

        return $this->jsonResponse($response, $message);
    }
}