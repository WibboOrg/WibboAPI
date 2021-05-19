<?php 
namespace App\Controller\Map;

use App\Controller\DefaultController;
use App\Models\News;
use App\Models\ForumThreads;

class MapController extends DefaultController
{
    public function getArticles($request, $response, $args)
    {
        $lastNews = News::select('id', 'link_keyword', 'topstory_image', 'title', 'snippet', 'timestamp')->get();

        return $this->jsonResponse($response, $lastNews);
    }

    public function getForums($request, $response, $args)
    {
        $post = ForumThreads::select('id')->orderBy('lastpost_date', 'DESC')->limit(50000)->get();

        return $this->jsonResponse($response, $post);
    }
}