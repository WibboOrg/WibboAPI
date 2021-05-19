<?php
namespace App\Controller\Forum;

use App\Controller\DefaultController;
use App\Models\Forum;
use App\Models\ForumPosts;
use App\Models\ForumThreads;
use App\Models\StaffLog;
use App\Models\User;
use Exception;
use App\Service\Cache;

class ForumController extends DefaultController
{
    public function getForum($request, $response, $args)
    {
        $limitpage = 20;
        $ctgr = (is_numeric($args['category'])) ? $args['category'] : 0;
        $search = (!empty($_GET['search'])) ? urldecode($_GET['search']) : '';

        if ($ctgr == 0)
            $total = ForumThreads::count();
        else if(!empty($search))
            $total = 1;//ForumThreads::where('title', 'LIKE', $search)->count();
        else 
            $total = ForumThreads::where('type', 1)->where('categorie', $ctgr)->count();

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
        if ($ctgr == 0)
            $post = ForumThreads::orderBy('lastpost_date', 'DESC')->forPage($currentPage, $limitpage)->get();
        else if(!empty($search))
            $post = ForumThreads::where('title', 'LIKE', '%' . str_replace(array('%', '_'), array('\%', '\_'), $search) . '%')->orderBy('lastpost_date', 'DESC')->forPage($currentPage, $limitpage)->get();
        else {
            $post = ForumThreads::where('categorie', $ctgr)->where('type', 1)->orderBy('lastpost_date', 'DESC')->forPage($currentPage, $limitpage)->get();
            $postPin = ForumThreads::where('categorie', $ctgr)->where('type', 2)->orderBy('lastpost_date', 'DESC')->forPage($currentPage, $limitpage)->get();
        }

        $message = [
            'post' => $post,
            'postPin' => $postPin,
            'totalPage' => $totalPage,
        ];

        return $this->jsonResponse($response, $message);
    }

    public function viewSujet($request, $response, $args)
    {
        if (empty($args['id']) || !is_numeric($args['id'])) {
            throw new Exception('not-found', 404);
        }

        $limitpage = 10;
        $sujet = ForumThreads::where('id', $args['id'])->first();
        if (!$sujet) {
            throw new Exception('not-found', 404);
        }

        $total = ForumPosts::where('threadid', $args['id'])->count();
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

        ForumThreads::where('id', $args['id'])->increment('views');

        $topicmessage = ForumPosts::select('users.look', 'users.username', 'users.rank', 'cms_forum_posts.id', 'cms_forum_posts.id_auteur', 'cms_forum_posts.message', 'cms_forum_posts.date')->join('users', 'cms_forum_posts.id_auteur', '=', 'users.id')->where('threadid', $sujet->id)->orderBy('id', 'ASC')->forPage($currentPage, $limitpage)->get();

        $message = [
            'sujet' => $sujet,
            'userMessage' => $topicmessage,
            'totalPage' => $totalPage
        ];

        return $this->jsonResponse($response, $message);
    }

    public function editPost($request, $response, $args)
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

        $post = ForumPosts::where('id', '=', $args['id'])->first();

        if (!$post) {
            throw new Exception('error', 400);
        }

        $sujet = ForumThreads::where('id', '=', $post->threadid)->first();

        if (!$sujet) {
            throw new Exception('error', 400);
        }

        $ctgr = $sujet->categorie;

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
            StaffLog::insert([
                'pseudo' => $user->username,
                'action' => 'Forum edit commentaire: ' . $args['id'],
                'date' => time(),
            ]);
        }

        ForumPosts::where('id', '=', $args['id'])->update(['message' => $data->message]);

        return $this->jsonResponse($response, null);
    }

    public function postSujet($request, $response, $args)
    {
        $input = $request->getParsedBody();

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['sujet', 'category', 'message']);

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('account_created', 'rank', 'username', 'mail_valide', 'look', 'motto')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if (empty($data->category) || !is_numeric($data->category)) {
            throw new Exception('error', 400);
        }

        $cat = Forum::where('id', $data->category);
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

        if ($user->mail_valide == 0) {
            throw new Exception('forum.mail-valid', 400);
        }

        if ($data->category == 8 && $user->rank < 6) {
            throw new Exception('forum.staff', 400);
        }

        if ($data->category == 7 && $user->rank < 3) {
            throw new Exception('forum.staff', 400);
        }

        $last = ForumThreads::orderBy('date', 'DESC')->limit(1)->first();
        if ($last->author == $user->username && $user->rank < 6) {
            throw new Exception('forum.wait', 400);
        }

        $thread = ForumThreads::create([
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

        $post = ForumPosts::create([
            'threadid' => $threadId,
            'message' => $data->message,
            'author' => $user->username,
            'date' => time(),
            'look' => $user->look,
            'id_auteur' => $userId,
            'rank' => $user->rank,
            'motto' => $user->motto,
        ]);

        ForumThreads::where('id', '=', $threadId)->update(['main_post' => $post->id]);

        $message = ['id' => $threadId];

        return $this->jsonResponse($response, $message);
    }

    public function deplaceSujet($request, $response, $args)
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

        $sujet = ForumThreads::where('id', $args['id'])->first();
        if (!$sujet) {
            throw new Exception('error', 400);
        }
        if ($sujet->author != $user->username && $user->rank < 6) {
            throw new Exception('permission', 400);
        }

        if ($sujet->author != $user->username && $user->rank >= 6) {
            StaffLog::insert([
                'pseudo' => $user->username,
                'action' => 'Forum deplacement sujet: ' . $args['id'],
                'date' => time(),
            ]);
        }

        ForumThreads::where('id', '=', $args['id'])->update(['categorie' => $data->category]);

        return $this->jsonResponse($response, null);
    }

    public function comment($request, $response, $args)
    {
        $input = $request->getParsedBody();

        $data = json_decode(json_encode($input), false);

        $this->requireData($data, ['message']);

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('account_created', 'mail_valide', 'username', 'motto', 'look', 'rank')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if (empty($args['id']) || !is_numeric($args['id'])) {
            throw new Exception('error', 400);
        }

        $sujet = ForumThreads::where('id', $args['id'])->limit(1)->first();
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

        if ($user->mail_valide == 0) {
            throw new Exception('forum.mail-valid', 400);
        }

        $posts = $sujet->posts + 1;
        $post = ForumPosts::create([
            'threadid' => $args['id'],
            'message' => $data->message,
            'author' => $user->username,
            'date' => time(),
            'motto' => $user->motto,
            'look' => $user->look,
            'id_auteur' => $userId,
            'rank' => $user->rank]);

        ForumThreads::where('id', '=', $args['id'])->update([
            'lastpost_author' => $user->username,
            'lastpost_date' => time(),
            'posts' => $posts]);

        return $this->jsonResponse($response, $post);
    }

    public function deletePost($request, $response, $args)
    {
        $input = $request->getParsedBody();

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if (empty($args['id']) || !is_numeric($args['id']))
            throw new Exception('error', 400);

        $post = ForumPosts::where('id', '=', $args['id'])->first();

        if (!$post)
            throw new Exception('error', 400);

        if ($user->rank < 8 && $userId != $post->id_auteur)
            throw new Exception('permission', 400);

        $sujet = ForumThreads::where('id', $post->threadid)->first();
        if (!$sujet)
            throw new Exception('error', 400);

        if ($sujet->main_post == $post->id) {
            ForumPosts::where('threadid', $sujet->id)->delete();
            ForumThreads::where('id', $post->threadid)->delete();
        } else {
            ForumPosts::where('id', '=', $args['id'])->delete();
            ForumThreads::where('id', $post->threadid)->decrement('posts');
        }

        return $this->jsonResponse($response, null);
    }

    public function statutSujet($request, $response, $args)
    {
        $input = $request->getParsedBody();

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank', 'username')->first();

        if(!$user) throw new Exception('disconnect', 401);

        if (empty($args['id']) || !is_numeric($args['id']))
            throw new Exception('error', 400);
        
        $thread = ForumThreads::where('id', '=', $args['id'])->first();
        if (!$thread)
            throw new Exception('error', 400);

        if ($user->rank < 6 && $user->username != $thread->author) 
            throw new Exception('permission', 400);

        if ($thread->statut == 1)
            ForumThreads::where('id', '=', $args['id'])->update(['statut' => 0]);
        else
            ForumThreads::where('id', '=', $args['id'])->update(['statut' => 1]);

        return $this->jsonResponse($response, null);
    }

    public function epingleSujet($request, $response, $args)
    {
        $input = $request->getParsedBody();

        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('rank', 'username')->first();

        if(!$user) 
            throw new Exception('disconnect', 401);

        if (empty($args['id']) || !is_numeric($args['id']))
            throw new Exception('error', 400);
        
        $sujet = ForumThreads::where('id', '=', $args['id'])->first();
        if (!$sujet)
            throw new Exception('error', 400);

        if ($user->rank < 10)
            throw new Exception('permission', 400);

        if ($sujet->type == 1 && $args['flag'] == "true") {
            StaffLog::insert([
                'pseudo' => $user->username,
                'action' => 'Forum épinglé sujet: ' . $args['id'],
                'date' => time(),
            ]);

            ForumThreads::where('id', '=', $args['id'])->update(['type' => 2]);
        } else if ($sujet->type == 2 && $args['flag'] == "false") {
            StaffLog::insert([
                'pseudo' => $user->username,
                'action' => 'Forum déépinglé sujet: ' . $args['id'],
                'date' => time(),
            ]);

            ForumThreads::where('id', '=', $args['id'])->update(['type' => 1]);
        }

        return $this->jsonResponse($response, null);
    }
}
