<?php

namespace Src\Middleware;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Src\Services\GoogleOauthService;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $headers = $request->getHeaders();
        if (!isset($headers['authorization'][0])) {
            return new JsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $authHeader = $headers['authorization'][0];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            return new JsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $jwt = substr($authHeader, 7);
        try {
            $googleOauth2Service = new GoogleOauthService();
            $user = $googleOauth2Service->validateJwtToken($jwt);
            $request = $request->withAttribute('user', $user);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 401);
        }

        return $handler->handle($request);
    }
}
