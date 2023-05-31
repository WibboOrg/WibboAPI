<?php
namespace App\Controller\Community;

use App\Controller\DefaultController;
use App\Models\News;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class ArticleController extends DefaultController
{
    public function getNewList(Request $request, Response $response, array $args): Response
    {
        $totalPage = 0;
        $limitpage = 50;

        $total = News::count();
        $totalPage = ceil($total / $limitpage);

        if (!empty($_GET['page']) and is_numeric($_GET['page'])) {
            $currentPage = intval($_GET['page']);
            if ($currentPage > $totalPage) {
                $currentPage = $totalPage;
            }
        } else {
            $currentPage = 1;
        }

        $listNews = News::select('id', 'title', 'link_keyword')->where('timestamp', '<=', time())->forPage($currentPage, $limitpage)->orderBy('timestamp', 'DESC')->get();
    
        $message = [
            'totalPage' => $totalPage,
            'listNews' => $listNews
        ];

        return $this->jsonResponse($response, $message);
    }
    
    public function getNewLast(Request $request, Response $response, array $args): Response
    {
        $cacheData = $this->cache->get(5);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        $lastNews = News::select('id', 'link_keyword', 'topstory_image', 'title', 'snippet', 'timestamp')->where('timestamp', '<=', time())->orderBy('timestamp', 'DESC')->limit(10)->get();
        
        $message = [
            'lastNews' => $lastNews
        ];

        $this->cache->save($message);

        return $this->jsonResponse($response, $message);
    }

    public function getNews(Request $request, Response $response, array $args): Response
    {
        $cacheData = $this->cache->get(60);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        if(!!empty($args['id']) || !is_numeric($args['id'])) {
            throw new Exception('not-found', 404);
        }

        $new = News::leftjoin('user', 'cms_news.author_id', '=', 'user.id')->where('cms_news.id', $args['id'])->select('cms_news.*', 'user.username', 'user.look')->first();
        if (!$new) {
            throw new Exception('not-found', 404);
        }

        $message = [
            'body' => $new
        ];

        $this->cache->save($message);

        return $this->jsonResponse($response, $message);
    }
}