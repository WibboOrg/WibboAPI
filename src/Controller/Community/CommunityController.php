<?php
namespace App\Controller\Community;

use App\Controller\DefaultController;
use App\Models\CategoryStaff;
use App\Models\Staff;
use App\Models\Groups;
use App\Models\GuildMembership;
use App\Models\User;
use App\Models\UserPhoto;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

class CommunityController extends DefaultController
{
    public function getGroupe(Request $request, Response $response, array $args): Response
    {
        $cacheData = $this->cache->get(60);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        if (empty($args['groupId']) || !is_numeric($args['groupId'])) 
            throw new Exception('not-found', 404);

        $group = Groups::where('id', $args['groupId'])->first();
        if (!$group) throw new Exception('not-found', 404);

        $memberCount = GuildMembership::where('group_id', $args['groupId'])->count();
        
        $owner = User::where('id', $group->owner_id)->select('username', 'look')->first();

        if(!$owner) throw new Exception('not-found', 404);

        $message = [
            'group' => $group,
            'memberCount' => $memberCount,
            'owner' => $owner
        ];

        $this->cache->save($message);

        return $this->jsonResponse($response, $message);
    }

    public function getStaff(Request $request, Response $response, array $args): Response
    {
        $cacheData = $this->cache->get(2);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        $staff = Staff::join('user', 'cms_staff.userid', '=', 'user.id')
            ->select('user.id', 'cms_staff.rank', 'cms_staff.function', 'cms_staff.social_insta', 'cms_staff.social_discord', 'user.username', 'user.look', 'user.motto', 'user.last_offline', 'user.online')
            ->get();

        $boxstaff = CategoryStaff::orderBy('id', 'ASC')->select('rank', 'rank_nom')->get();

        $message = [
            'staff' => $staff,
            'boxstaff' => $boxstaff
        ];

        $this->cache->save($message);

        return $this->jsonResponse($response, $message);
    }

    public function getPhotos(Request $request, Response $response, array $args): Response
    {
        $currentPage = 0;
        if (!empty($_GET['page']) and is_numeric($_GET['page'])) {
            $currentPage = intval($_GET['page']);
        }

        $cacheData = $this->cache->get(10, $currentPage);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        $photos = UserPhoto::select('photo', 'username', 'look', 'time')->join('user', 'user_photo.user_id', '=', 'user.id')->groupBy('user_photo.user_id')->orderBy('user_photo.time', 'desc')->where('user.is_banned', '0')->forPage($currentPage, 20)->get();

        $message = [
            'photos' => $photos
        ];

        $this->cache->save($message, $currentPage);

        return $this->jsonResponse($response, $message);
    }
}