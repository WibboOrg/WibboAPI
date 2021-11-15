<?php 
namespace App\Controller\Map;

use App\Controller\DefaultController;
use App\Models\News;
use App\Models\ForumThread;
use Slim\Http\Request;
use Slim\Http\Response;

class MapController extends DefaultController
{
    public function getArticles(Request $request, Response $response, array $args): Response
    {
        $lastNews = News::select('id', 'link_keyword', 'topstory_image', 'title', 'snippet', 'timestamp')->get();

        $message = [
            'lastNews' => $lastNews
        ];

        return $this->jsonResponse($response, $message);
    }

    public function getForums(Request $request, Response $response, array $args): Response
    {
        $posts = ForumThread::select('id')->orderBy('lastpost_date', 'DESC')->limit(50000)->get();

        $message = [
            'posts' => $posts
        ];

        return $this->jsonResponse($response, $message);
    }
}