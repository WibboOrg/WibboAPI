<?php
namespace App\Controller\Forum;

use App\Controller\DefaultController;
use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\LogStaff;
use App\Models\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class ForumController extends DefaultController
{
    public function getForum(Request $request, Response $response, array $args): Response
    {
        $limitpage = 20;
        $ctgr = (is_numeric($args['category'])) ? $args['category'] : 0;
        $search = (!empty($_GET['search'])) ? urldecode($_GET['search']) : '';

        if(!empty($search))
            $total = 1;//ForumThread::where('title', 'LIKE', $search)->count();
        else if ($ctgr == 0)
            $total = ForumThread::count();
        else 
            $total = ForumThread::where('type', 1)->where('categorie', $ctgr)->count();

        $totalPage = ceil($total / $limitpage);

        if (!empty($_GET['page']) && is_numeric($_GET['page'])) {
            $currentPage = intval($_GET['page']);

            if ($currentPage > $totalPage) {
                $currentPage = $totalPage;
            }
        } else {
            $currentPage = 1;
        }

        $postPin = [];
        if(!empty($search))
            $post = ForumThread::where('title', 'LIKE', '%' . str_replace(array('%', '_'), array('\%', '\_'), $search) . '%')->orderBy('lastpost_date', 'DESC')->forPage($currentPage, $limitpage)->get();
        else if ($ctgr == 0)
            $post = ForumThread::orderBy('lastpost_date', 'DESC')->forPage($currentPage, $limitpage)->get();
        else {
            $post = ForumThread::where('categorie', $ctgr)->where('type', 1)->orderBy('lastpost_date', 'DESC')->forPage($currentPage, $limitpage)->get();
            $postPin = ForumThread::where('categorie', $ctgr)->where('type', 2)->orderBy('lastpost_date', 'DESC')->forPage($currentPage, $limitpage)->get();
        }

        $message = [
            'post' => $post,
            'postPin' => $postPin,
            'totalPage' => $totalPage,
        ];

        return $this->jsonResponse($response, $message);
    }

    public function viewSujet(Request $request, Response $response, array $args): Response
    {
        if (empty($args['id']) || !is_numeric($args['id'])) {
            throw new Exception('not-found', 404);
        }

        $limitpage = 10;
        $sujet = ForumThread::where('id', $args['id'])->first();
        if (!$sujet) {
            throw new Exception('not-found', 404);
        }

        $total = ForumPost::where('threadid', $args['id'])->count();
        $totalPage = ceil($total / $limitpage);

        if (!empty($_GET['page']) && is_numeric($_GET['page'])) {
            $currentPage = intval($_GET['page']);

            if ($currentPage < 1) {
                $currentPage = 1;
            } else if ($currentPage > $totalPage) {
                $currentPage = $totalPage;
            }
        } else {
            $currentPage = 1;
        }

        ForumThread::where('id', $args['id'])->increment('views');

        $topicmessage = ForumPost::select('user.look', 'user.username', 'user.rank', 'cms_forum_post.id', 'cms_forum_post.id_auteur', 'cms_forum_post.message', 'cms_forum_post.date')->join('user', 'cms_forum_post.id_auteur', '=', 'user.id')->where('threadid', $sujet->id)->orderBy('id', 'ASC')->forPage($currentPage, $limitpage)->get();

        $message = [
            'sujet' => $sujet,
            'userMessage' => $topicmessage,
            'totalPage' => $totalPage
        ];

        return $this->jsonResponse($response, $message);
    }

    public function editPost(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['message']);

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('username', 'rank')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if (empty($args['id']) || !is_numeric($args['id'])) {
            throw new Exception('error', 400);
        }

        $post = ForumPost::where('id', '=', $args['id'])->first();

        if (!$post) {
            throw new Exception('error', 400);
        }

        $sujet = ForumThread::where('id', '=', $post->threadid)->first();

        if (!$sujet) {
            throw new Exception('error', 400);
        }

        if ($post->id_auteur != $userId && $user->rank < 6) {
            throw new Exception('permission', 400);
        }

        if (empty($data->message)) {
            throw new Exception('forum.empty-message', 400);
        }

        if (strlen($data->message) > 10000) {
            throw new Exception('forum.big-message', 400);
        }

        if ($post->id_auteur != $userId && $user->rank >= 6) {
            LogStaff::insert([
                'pseudo' => $user->username,
                'action' => 'Édition sur le forum du commentaire : ' . $args['id'],
                'date' => time(),
            ]);
        }

        ForumPost::where('id', '=', $args['id'])->update(['message' => $data->message]);

        return $this->jsonResponse($response, []);
    }

    public function postSujet(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['sujet', 'category', 'message']);

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('account_created', 'rank', 'username', 'mail', 'look', 'motto')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if (empty($data->category) || !is_numeric($data->category)) {
            throw new Exception('error', 400);
        }

        $cat = ForumCategory::where('id', $data->category);
        if (!$cat) {
            throw new Exception('error', 400);
        }

        if (empty($data->sujet) || strlen($data->message) < 10) {
            throw new Exception('forum.empty-sujet', 400);
        }

        if (strlen($data->sujet) > 50) {
            throw new Exception('forum.big-sujet', 400);
        }

        if (strlen($data->message) > 10000) {
            throw new Exception('forum.big-message', 400);
        }

        if (is_numeric($user->account_created) && $user->account_created > time() - 60 * 60 * 24) {
            throw new Exception('forum.account-age', 400);
        }

        if (empty($user->mail)) {
            throw new Exception('forum.mail-valid', 400);
        }

        if ($data->category == 8 && $user->rank < 8) {
            throw new Exception('forum.staff', 400);
        }

        if ($data->category == 7 && $user->rank < 3) {
            throw new Exception('forum.staff', 400);
        }

        $last = ForumThread::orderBy('date', 'DESC')->limit(1)->first();
        if ($last != null && $last->author == $user->username && $user->rank < 6) {
            throw new Exception('forum.wait', 400);
        }

        $thread = ForumThread::create([
            'type' => 1,
            'title' => $data->sujet,
            'author' => $user->username,
            'date' => time(),
            'lastpost_author' => $user->username,
            'lastpost_date' => time(),
            'posts' => 0,
            'main_post' => 0,
            'categorie' => $data->category,
            'statut' => 0,
        ]);

        $threadId = $thread->id;

        $post = ForumPost::create([
            'threadid' => $threadId,
            'message' => $data->message,
            'author' => $user->username,
            'date' => time(),
            'look' => $user->look,
            'id_auteur' => $userId,
            'rank' => $user->rank,
            'motto' => $user->motto,
        ]);

        ForumThread::where('id', '=', $threadId)->update(['main_post' => $post->id]);

        $message = ['id' => $threadId];

        return $this->jsonResponse($response, $message);
    }

    public function deplaceSujet(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['category']);

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('username', 'rank')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if (empty($args['id']) || !is_numeric($args['id'])) {
            throw new Exception('error', 400);
        }

        $sujet = ForumThread::where('id', $args['id'])->first();
        if (!$sujet) {
            throw new Exception('error', 400);
        }
        if ($sujet->author != $user->username && $user->rank < 6) {
            throw new Exception('permission', 400);
        }

        if ($sujet->author != $user->username && $user->rank >= 6) {
            LogStaff::insert([
                'pseudo' => $user->username,
                'action' => 'Déplacement du sujet : ' . $args['id'],
                'date' => time(),
            ]);
        }

        ForumThread::where('id', '=', $args['id'])->update(['categorie' => $data->category]);

        return $this->jsonResponse($response, []);
    }

    public function comment(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['message']);

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('account_created', 'mail', 'username', 'motto', 'look', 'rank')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if (empty($args['id']) || !is_numeric($args['id'])) {
            throw new Exception('error', 400);
        }

        $sujet = ForumThread::where('id', $args['id'])->limit(1)->first();
        if (!$sujet) {
            throw new Exception('error', 400);
        }
        if ($sujet->statut == 1) {
            throw new Exception('error', 400);
        }
        if (empty($data->message)) {
            throw new Exception('forum.empty-message', 400);
        }
        if (is_numeric($user->account_created) && $user->account_created > time() - 60 * 60 * 24) {
            throw new Exception('forum.account-age', 400);
        }

        if (empty($user->mail)) {
            throw new Exception('forum.mail-valid', 400);
        }

        $posts = $sujet->posts + 1;
        $post = ForumPost::create([
            'threadid' => $args['id'],
            'message' => $data->message,
            'author' => $user->username,
            'date' => time(),
            'motto' => $user->motto,
            'look' => $user->look,
            'id_auteur' => $userId,
            'rank' => $user->rank]);

        ForumThread::where('id', '=', $args['id'])->update([
            'lastpost_author' => $user->username,
            'lastpost_date' => time(),
            'posts' => $posts]);

        $message = [
            'post' => $post
        ];

        return $this->jsonResponse($response, $message);
    }

    public function deletePost(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if (empty($args['id']) || !is_numeric($args['id']))
            throw new Exception('error', 400);

        $post = ForumPost::where('id', '=', $args['id'])->first();

        if (!$post)
            throw new Exception('error', 400);

        if ($user->rank < 8 && $userId != $post->id_auteur)
            throw new Exception('permission', 400);

        $sujet = ForumThread::where('id', $post->threadid)->first();
        if (!$sujet)
            throw new Exception('error', 400);

        if ($sujet->main_post == $post->id) {
            ForumPost::where('threadid', $sujet->id)->delete();
            ForumThread::where('id', $post->threadid)->delete();
        } else {
            ForumPost::where('id', '=', $args['id'])->delete();
            ForumThread::where('id', $post->threadid)->decrement('posts');
        }

        return $this->jsonResponse($response, []);
    }

    public function statutSujet(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank', 'username')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if (empty($args['id']) || !is_numeric($args['id']))
            throw new Exception('error', 400);
        
        $thread = ForumThread::where('id', '=', $args['id'])->first();
        if (!$thread)
            throw new Exception('error', 400);

        if ($user->rank < 6 && $user->username != $thread->author) 
            throw new Exception('permission', 400);

        if ($thread->statut == 1)
            ForumThread::where('id', '=', $args['id'])->update(['statut' => 0]);
        else
            ForumThread::where('id', '=', $args['id'])->update(['statut' => 1]);

        return $this->jsonResponse($response, []);
    }

    public function epingleSujet(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank', 'username')->first();

        if(!$user) 
            throw new Exception('disconnect', 401);

        if (empty($args['id']) || !is_numeric($args['id']))
            throw new Exception('error', 400);
        
        $sujet = ForumThread::where('id', '=', $args['id'])->first();
        if (!$sujet)
            throw new Exception('error', 400);

        if ($user->rank < 8)
            throw new Exception('permission', 400);

        if ($sujet->type == 1 && $args['flag'] == "true") {
            LogStaff::insert([
                'pseudo' => $user->username,
                'action' => 'Topic épinglé n°: ' . $args['id'],
                'date' => time(),
            ]);

            ForumThread::where('id', '=', $args['id'])->update(['type' => 2]);
        } else if ($sujet->type == 2 && $args['flag'] == "false") {
            LogStaff::insert([
                'pseudo' => $user->username,
                'action' => 'Forum déépinglé n°: ' . $args['id'],
                'date' => time(),
            ]);

            ForumThread::where('id', '=', $args['id'])->update(['type' => 1]);
        }

        return $this->jsonResponse($response, []);
    }
}
