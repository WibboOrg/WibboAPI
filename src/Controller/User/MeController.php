<?php
namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Models\ForumThread;
use App\Models\News;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserPremium;
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

        $timevipexpire = 0;
        $vip = UserPremium::where('user_id', $userId)->orderBy('timestamp_expire', 'DESC')->first();
        if ($vip) {
            if ($vip->timestamp_expire <= time()) {
                UserPremium::where('user_id', $userId)->delete();

                UserBadge::where('badge_id', 'WPREMIUM')->where('user_id', $userId)->delete();

                User::where('id', $userId)->where('rank', '2')->update(['rank' => '1']);
            }
            $timevipexpire = $vip->timestamp_expire - time();
            $timevipexpire = intval($timevipexpire / 60 / 60 / 24);
        }

        $news = News::select('id', 'topstory_image', 'title', 'snippet', 'link_keyword')->where('timestamp', '<=', time())->orderBy('timestamp', 'DESC')->limit(5)->get();
        $lastforumthread = ForumThread::join('user', 'cms_forum_thread.author', '=', 'user.username')->select('cms_forum_thread.id', 'cms_forum_thread.title', 'cms_forum_thread.lastpost_author', 'cms_forum_thread.lastpost_date', 'user.look')->where('cms_forum_thread.type', 1)->orderBy('cms_forum_thread.lastpost_date', 'DESC')->limit(10)->get();

        $message = [
            'vip_time' => $timevipexpire,
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
