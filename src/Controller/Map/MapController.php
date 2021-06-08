<?php 
namespace App\Controller\Map;

use App\Controller\DefaultController;
use App\Models\News;
use App\Models\ForumThreads;
use Slim\Http\Request;
use Slim\Http\Response;

class MapController extends DefaultController
{
    public function getArticles(Request $request, Response $response, array $args): Response
    {
        $lastNews = News::select('id', 'link_keyword', 'topstory_image', 'title', 'snippet', 'timestamp')->get();

        return $this->jsonResponse($response, $lastNews);
    }

    public function getForums(Request $request, Response $response, array $args): Response
    {
        $post = ForumThreads::select('id')->orderBy('lastpost_date', 'DESC')->limit(50000)->get();

        return $this->jsonResponse($response, $post);
    }
}