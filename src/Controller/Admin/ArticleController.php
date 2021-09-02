<?php
namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Models\News;
use App\Models\StaffLog;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class ArticleController extends DefaultController
{
    public function get(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $news = News::select('id', 'title', 'snippet')->orderBy('id', 'DESC')->limit(20)->get();

        $message = [
            'news' => $news
        ];

        return $this->jsonResponse($response, $message);
    }

    public function getNew(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $id = $args['id'];

        if (empty($id) || !is_numeric($id)) {
            throw new Exception('error', 400);
        }

        $new = News::select('id', 'title', 'snippet', 'topstory_image', 'body', 'link_keyword', 'author', 'timestamp')->where('id', $id)->first();

        $message = [
            'new' => $new
        ];

        return $this->jsonResponse($response, $message);
    }

    public function post(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['title', 'desc', 'url', 'content', 'keyword', 'author', 'time']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $title = $data->title;
        $desc = $data->desc;
        $url = $data->url;
        $content = $data->content;
        $author = $data->author;
        $datetime = $data->time;
        $keyword = $data->keyword;

        if (empty($title) || empty($desc) || empty($url) || empty($content) || empty($author) || empty($datetime) || empty($keyword)) {
            throw new Exception('error1', 400);
        }

        if (($timestamp = strtotime($datetime)) === false) {
            throw new Exception('error2', 400);
        }

        if ($timestamp < time()) {
            $timestamp = time();
        }

        $id = News::insertGetId([
            'title' => $title,
            'snippet' => $desc,
            'topstory_image' => $url,
            'body' => $content,
            'timestamp' => $timestamp,
            'link_keyword' => $keyword,
            'author' => $author,
            'author_id' => $userId,
        ]);

        StaffLog::insert([
            'pseudo' => $user->username,
            'action' => 'Création d\'un article: ' . $id,
            'date' => time()
        ]);

        return $this->jsonResponse($response, []);
    }

    public function patch(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['title', 'snippet', 'topstory_image', 'body', 'link_keyword', 'author', 'timestamp']);

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 8) {
            throw new Exception('permission', 403);
        }

        $id = $args['id'];
        if (empty($id) || !is_numeric($id)) {
            throw new Exception('error', 400);
        }

        $titre = $data->title;
        $desc = $data->snippet;
        $url = $data->topstory_image;
        $content = $data->body;
        $keyword = $data->link_keyword;
        $author = $data->author;
        $datetime = $data->timestamp;

        if (empty($titre) || empty($desc) || empty($url) || empty($content) || empty($author) || empty($keyword)) {
            throw new Exception('error', 400);
        }

        if (($timestamp = strtotime($datetime)) === false) {
            $timestamp = 0;
        }

        $new = News::select('id', 'timestamp')->where('id', $id)->first();
        if (!$new) {
            throw new Exception('error', 400);
        }

        if ($timestamp <= 0) {
            $timestamp = $new->timestamp;
        }

        News::where('id', $id)->update([
            'title' => $titre,
            'snippet' => $desc,
            'topstory_image' => $url,
            'body' => $content,
            'link_keyword' => $keyword,
            'author' => $author,
            'timestamp' => $timestamp,
        ]);

        StaffLog::insert([
            'pseudo' => $user->username,
            'action' => 'Modification d\'un article: ' . $new->id,
            'date' => time()
        ]);

        return $this->jsonResponse($response, []);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank', 'username')->first();
        if(!$user) throw new Exception('disconnect', 401);
                
        if ($user->rank < 13) {
            throw new Exception('permission', 403);
        }

        $id = $args['id'];

        if (empty($id) || !is_numeric($id)) {
            throw new Exception('error', 400);
        }

        $new = News::where('id', $id)->first();
        if (!$new) {
            throw new Exception('error', 400);
        }

        StaffLog::insert([
            'pseudo' => $user->username,
            'action' => 'Supression d\'un article: ' . $new->id,
            'date' => time()
		]);
		
        $new->delete();

        return $this->jsonResponse($response, []);
    }
}
