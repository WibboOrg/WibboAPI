<?php
namespace App\Handler;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

final class NotFound extends \Slim\Handlers\NotFound
{
    public function __invoke(Request $request, Response $response)
    {
        $statusCode = 404;
        $data = [
            'message' => "404: Not found",
            'status' => 'notfound',
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