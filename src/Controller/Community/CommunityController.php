<?php
namespace App\Controller\Community;

use App\Controller\DefaultController;
use App\Models\PageStaff;
use App\Models\StaffPage;
use App\Models\Groups;
use App\Models\GroupMembres;
use App\Models\User;
use App\Models\UserPhotos;
use Exception;

class CommunityController extends DefaultController
{
    public function getGroupe($request, $response, $args)
    {
        $cacheData = $this->cache->get(60);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        if (empty($args['groupId']) || !is_numeric($args['groupId'])) 
            throw new Exception('not-found', 404);

        $group = Groups::where('id', $args['groupId'])->first();
        if (!$group) throw new Exception('not-found', 404);

        $memberCount = GroupMembres::where('group_id', $args['groupId'])->count();
        
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

    public function getStaff($request, $response)
    {
        $cacheData = $this->cache->get(60);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        $staff = StaffPage::join('users', 'cms_page_staff.userid', '=', 'users.id')
            ->select('users.id', 'cms_page_staff.rank', 'cms_page_staff.function', 'cms_page_staff.social_insta', 'cms_page_staff.social_discord', 'users.username', 'users.look', 'users.motto', 'users.last_offline', 'users.online')
            ->get();

        $boxstaff = PageStaff::orderBy('id', 'ASC')->select('rank', 'rank_nom', 'color', 'colonne')->get();

        $message = [
            'staff' => $staff,
            'boxstaff' => $boxstaff
        ];

        $this->cache->save($message);

        return $this->jsonResponse($response, $message);
    }

    public function getPhotos($request, $response)
    {
        $currentPage = 0;
        if (!empty($_GET['page']) and is_numeric($_GET['page'])) {
            $currentPage = intval($_GET['page']);
        }

        $cacheData = $this->cache->get(10, $currentPage);
        if(!empty($cacheData)) return $this->jsonResponse($response, $cacheData);

        $photos = UserPhotos::select('photo', 'username', 'look', 'time')->join('users', 'user_photos.user_id', '=', 'users.id')->groupBy('user_photos.user_id')->orderBy('user_photos.time', 'desc')->where('users.online', '1')->forPage($currentPage, 20)->get();

        $message = [
            'photos' => $photos
        ];

        $this->cache->save($message, $currentPage);

        return $this->jsonResponse($response, $message);
    }
}