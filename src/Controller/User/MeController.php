<?php
namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Models\ForumThread;
use App\Models\News;
use App\Models\User;
use App\Models\UserBadge;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class MeController extends DefaultController
{
    public function me(Request $request, Response $response, array $args): Response
    {
        $input = $request->getParsedBody();
        $userId = $input['decoded']->sub;

        $user = User::where('id', $userId)->select('account_created')->first();

		if(!$user) throw new Exception('disconnect', 401);

        $badgeMessage = $this->checkBadgeMiss($userId, $user->account_created);

        $news = News::select('id', 'topstory_image', 'title', 'snippet', 'link_keyword')->where('timestamp', '<=', time())->orderBy('timestamp', 'DESC')->limit(5)->get();
        $lastforumthread = ForumThread::join('user', 'cms_forum_thread.author', '=', 'user.username')->select('cms_forum_thread.id', 'cms_forum_thread.title', 'cms_forum_thread.lastpost_author', 'cms_forum_thread.lastpost_date', 'user.look')->where('cms_forum_thread.type', 1)->orderBy('cms_forum_thread.lastpost_date', 'DESC')->limit(10)->get();

        $message = [
            'news' => $news,
            'forum' => $lastforumthread,
            'badgemessage' => $badgeMessage
        ];

        return $this->jsonResponse($response, $message);
    }

    private function checkBadgeMiss(int $userId, int $accountCreated): array
    {
        $newBadges = array();
        
        if (!UserBadge::where('user_id', $userId)->where('badge_id', 'VIPFREE')->first()) {
            UserBadge::insert(['badge_id' => 'VIPFREE', 'user_id' => $userId]);
            $newBadges[] = "VIPFREE";
        }

        $yearUser = date("Y") - date("Y", $accountCreated);
        if($yearUser == 0)
          return $newBadges;

        for ($i = 0; $i < $yearUser; $i++) {
            if (UserBadge::where('user_id', $userId)->where('badge_id', 'WBI' . ($yearUser - $i))->first()) {
                break;
            }

            UserBadge::insert(['badge_id' => 'WBI' . ($yearUser - $i), 'user_id' => $userId]);
            $newBadges[] = 'WBI' . ($yearUser - $i);
        }

        return $newBadges;
    }
}
