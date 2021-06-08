<?php 
use Slim\Container;
use App\Handler\ApiError;
use App\Handler\NotFound;
use App\Handler\NotAllowed;
use Illuminate\Database\Capsule\Manager;
use App\Service\CacheService;
use App\Service\MailService;

$container = $app->getContainer();

$container['db'] = function (Container $container): Manager {
    $capsule = new Manager();
    $capsule->addConnection($container['settings']['db'], "default");

    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    
    return $capsule;
};

$container['errorHandler'] = function (): ApiError {
    return new ApiError();
};

$container['notFoundHandler'] = function (): NotFound {
    return new NotFound();
};

$container['notAllowedHandler'] = function (): NotAllowed {
    return new NotAllowed();
};

$container['cacheService'] = function (): CacheService {
    return new CacheService();
};

$container['mailService'] = function (): MailService {
    return new MailService();
};