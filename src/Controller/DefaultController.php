<?php
namespace App\Controller;

use Slim\Container;
use Slim\Http\Response;
use Exception;

class DefaultController
{
    public $container;
    public $db;
    public $cache;
    public $mail;

    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->db = $container->get('db');

        $this->cache = $container->get('cacheService');

        $this->mail = $container->get('mailService');
    }

    public function jsonResponse(Response $response, $message): Response
    {
        /*$result = [
            'code' => $code,
            'status' => $status,
            'message' => $message,
        ];*/

        return $response->withJson($message, 200);
    }

    public function requireData($data, $keyList)
    {
        foreach ($keyList as $key)
        {
            if(!property_exists($data, $key)) {
                throw new Exception('error', 400);
            }
        }
    }
}