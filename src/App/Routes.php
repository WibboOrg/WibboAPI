<?php

use App\Middleware\AuthMiddleware;

$app->group('/api/v1', function() use($app) {
    $app->group('', function() use($app) {
        $app->get('/userdata', '\App\Controller\User\UserDataController:get');
        $app->get('/me', '\App\Controller\User\MeController:me');
        $app->get('/groupe/{groupe}', '\App\Controller\Community\CommunityController:getGroupe');

        $app->group('/client', function() use($app) {
            $app->get('/data', '\App\Controller\Client\ClientController:getData');
        });

        $app->group('/settings', function() use($app) {
            $app->post('/general', '\App\Controller\Settings\SettingsController:postGeneral');
            $app->post('/password', '\App\Controller\Settings\SettingsController:postPassword');
            $app->post('/email', '\App\Controller\Settings\EmailController:formMail');
            $app->get('/email', '\App\Controller\Settings\EmailController:getMail');
            $app->put('/email/{code}', '\App\Controller\Settings\EmailController:getCode');
        });

        $app->group('/forum', function() use($app) {
            $app->post('/create', '\App\Controller\Forum\ForumController:postSujet');
            $app->post('/comment/{id}', '\App\Controller\Forum\ForumController:comment');
            $app->put('/deplace/{id}', '\App\Controller\Forum\ForumController:deplaceSujet');
            $app->put('/statut/{id}/{flag}', '\App\Controller\Forum\ForumController:statutSujet');
            $app->put('/epingle/{id}/{flag}', '\App\Controller\Forum\ForumController:epingleSujet');
            $app->delete('/delete/{id}', '\App\Controller\Forum\ForumController:deletePost');
            $app->post('/edit/{id}', '\App\Controller\Forum\ForumController:editPost');
        });
    })->add(new AuthMiddleware($app->getContainer()));

    $app->group('/map', function() use($app) {
        $app->get('/articles', '\App\Controller\Map\MapController:getArticles');
        $app->get('/forums', '\App\Controller\Map\MapController:getForums');
    });

    $app->post('/login', '\App\Controller\Auth\AuthController:post');
    $app->post('/register', '\App\Controller\User\RegisterController:post');
    
    $app->get('/search-user/{username}', '\App\Controller\Utils\UtilController:getSearchUser');
    
    $app->get('/room/{roomId}', '\App\Controller\Utils\RoomController:get');
    $app->get('/profil/{name}', 'App\Controller\User\ProfilController:get');
    $app->get('/profil-badges/{userId}', 'App\Controller\User\ProfilController:getBadges');
    $app->get('/group/{groupId}', '\App\Controller\Community\CommunityController:getGroupe');


    $app->put('/forgot/{code}', '\App\Controller\Utils\ForgotController:verifForgot');
    $app->post('/forgot', '\App\Controller\Utils\ForgotController:postForgot');

    $app->post('/callback_dedi', '\App\Controller\Shop\ShopController:verifDedipass');

    $app->post('/contact', '\App\Controller\Utils\ContactController:post');

    $app->get('/rares', '\App\Controller\CatalogItem\RareController:get');

    $app->group('/community', function() use($app) {
        $app->get('/staff', '\App\Controller\Community\CommunityController:getStaff');
        $app->get('/photos', '\App\Controller\Community\CommunityController:getPhotos');
        
        $app->get('/news/{id}', '\App\Controller\Community\ArticleController:getNews');
        $app->get('/news-list', '\App\Controller\Community\ArticleController:getNewList');
        $app->get('/news-last', '\App\Controller\Community\ArticleController:getNewLast');
    });

    $app->group('/classement', function() use($app) {
        $app->get('/gamer', '\App\Controller\Ranking\RankingController:getTop');
        $app->get('/joueur', '\App\Controller\Ranking\RankingController:getClassement');
        $app->get('/mazo', '\App\Controller\Ranking\RankingController:getTopMazo');
        $app->get('/influences', '\App\Controller\Ranking\RankingController:getInfluences');
        $app->get('/run', '\App\Controller\Ranking\RankingController:getTopRun');
    });

    $app->group('/forum', function() use($app) {
        $app->get('/category/{category}', '\App\Controller\Forum\ForumController:getForum');
        $app->get('/sujet/{id}', '\App\Controller\Forum\ForumController:viewSujet');
    });

    $app->group('/admin', function () use($app) {
        $app->get('/global-ban', '\App\Controller\Admin\BanController:get');
        $app->get('/global-history', '\App\Controller\Admin\Log\LogStaffController:get');
        $app->get('/global-command', '\App\Controller\Admin\Log\LogCommandController:get');
        $app->get('/global-shop', '\App\Controller\Admin\Log\LogShopController:get');
        
        $app->get('/stats', '\App\Controller\Admin\StatsController:get');

        $app->post('/roleplayitem', '\App\Controller\Admin\RoleplayItemController:post');
        $app->get('/roleplayitem', '\App\Controller\Admin\RoleplayItemController:get');
        $app->patch('/roleplayitem/{id}', '\App\Controller\Admin\RoleplayItemController:patch');
    
        $app->post('/article', '\App\Controller\Admin\ArticleController:post');
        $app->get('/article', '\App\Controller\Admin\ArticleController:get');
        $app->get('/article/{id}', '\App\Controller\Admin\ArticleController:getNew');
        $app->delete('/article/{id}', '\App\Controller\Admin\ArticleController:delete');
        $app->patch('/article/{id}', '\App\Controller\Admin\ArticleController:patch');

        $app->post('/ban', '\App\Controller\Admin\BanController:post');
        $app->delete('/ban/{username}', '\App\Controller\Admin\BanController:delete');

        $app->post('/flagme', '\App\Controller\Admin\FlagmeController:post');

        $app->get('/last-users', '\App\Controller\Admin\LastUsersController:get');
    
        $app->get('/ipstaff', '\App\Controller\Admin\IpstaffController:get');
        $app->post('/ipstaff', '\App\Controller\Admin\IpstaffController:post');

        $app->get('/staffs', '\App\Controller\Admin\StaffsController:get');
        $app->post('/staffs', '\App\Controller\Admin\StaffsController:post');

        $app->post('/badge', '\App\Controller\Admin\BadgeController:post');
        $app->delete('/badge/{username}/{code}', '\App\Controller\Admin\BadgeController:delete');
        $app->post('/badge-count', '\App\Controller\Admin\BadgeController:count');

        $app->post('/rank', '\App\Controller\Admin\RankController:post');
        $app->delete('/rank/{username}', '\App\Controller\Admin\RankController:delete');

        $app->post('/upload-badge', '\App\Controller\Admin\Upload\UploadBadgeController:post');
        $app->post('/upload-mp3', '\App\Controller\Admin\Upload\UploadMp3Controller:post');
        $app->post('/upload-image', '\App\Controller\Admin\Upload\UploadImageController:post');
    
        $app->post('/navigator', '\App\Controller\Admin\NavigatorController:post');
        $app->get('/navigator', '\App\Controller\Admin\NavigatorController:get');
        $app->delete('/navigator/{id}', '\App\Controller\Admin\NavigatorController:delete');
    
        $app->post('/user-ban', '\App\Controller\Admin\Log\LogBanController:post');
        $app->post('/user-flagme', '\App\Controller\Admin\Log\LogFlagmeController:post');
        $app->post('/user-chatlog', '\App\Controller\Admin\Log\LogChatController:post');
        $app->post('/user-command', '\App\Controller\Admin\Log\LogCommandController:post');
        $app->post('/user-account', '\App\Controller\Admin\UserAccountController:post');
        $app->post('/user-shop', '\App\Controller\Admin\Log\LogShopController:post');
    })->add(new AuthMiddleware($app->getContainer()));
});