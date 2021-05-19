<?php
namespace App\Handler;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

final class NotAllowed extends \Slim\Handlers\NotAllowed
{
    public function __invoke(Request $request, Response $response, array $methods)
    {
        $statusCode = 405;
        $data = [
            'message' => "405: Not allowed",
            'status' => 'notallowed',
            'code' => $statusCode,
        ];
        $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return $response
                ->withStatus($statusCode)
                ->withHeader('Content-type', 'application/problem+json')
                ->withHeader('Access-Control-Allow-Origin', getenv('APP_DOMAIN'))
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->write($body);
    }
}