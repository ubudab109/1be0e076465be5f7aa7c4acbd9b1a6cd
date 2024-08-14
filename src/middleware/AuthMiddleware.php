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
    /**
     * The function processes a server request by validating a JWT token in the authorization header
     * using Google OAuth2 service and returns a response based on the validation result.
     * 
     * @param ServerRequestInterface $request The `process` function you provided is a middleware
     * function that processes incoming HTTP requests. It checks for the presence of an 'Authorization'
     * header in the request, validates the JWT token, and adds the user information to the request
     * attributes if the token is valid. If the token is not valid or missing
     * @param RequestHandlerInterface $handler The `` parameter in the `process` function
     * represents the next middleware or handler in the request handling chain. When you call
     * `->handle()`, you are passing the request to the next middleware or handler in
     * the stack to continue processing the request.
     * 
     * @return ResponseInterface A `ResponseInterface` object is being returned. The response can be
     * either a successful response with the user attribute set in the request, or an error response
     * with a status code of 401 (Unauthorized) and an error message if there are issues with the
     * authorization header or validating the JWT token.
     */
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
