<?php
namespace App\Controller;

use Slim\Container;
use Slim\Http\Response;
use Illuminate\Database\Capsule\Manager;
use App\Service\CacheService;
use App\Service\MailService;
use Exception;

class DefaultController
{
    public Container $container;
    public Manager $db;
    public CacheService $cache;
    public MailService $mail;

    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->db = $container->get('db');
        $this->cache = $container->get('cacheService');
        $this->mail = $container->get('mailService');
    }
    
    public function jsonResponse(Response $response, array $message): Response
    {
        return $response->withJson($message, 200);
    }

    public function requireData(object $data, array $keyList)
    {
        foreach ($keyList as $key)
        {
            if(!property_exists($data, $key)) {
                throw new Exception('error', 400);
            }
        }
    }
}