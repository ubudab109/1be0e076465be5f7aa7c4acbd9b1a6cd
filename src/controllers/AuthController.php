<?php

// src/Controllers/AuthController.php
namespace Src\Controllers;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Src\Services\GoogleOauthService;

class AuthController
{
    private $googleOAuth2Service;

    public function __construct(GoogleOauthService $googleOAuth2Service)
    {
        $this->googleOAuth2Service = $googleOAuth2Service;
    }

    /**
     * The login function generates an authorization URL for Google OAuth2 authentication and redirects
     * the user to that URL.
     * 
     * @return ResponseInterface The `login()` function returns a `ResponseInterface` object. It either
     * returns a `RedirectResponse` object containing the authorization URL generated by the Google
     * OAuth2 service, or a `JsonResponse` object with an error message if there was an exception while
     * creating the authorization URL.
     */
    public function login(): ResponseInterface
    {
        try {
            $authUrl = $this->googleOAuth2Service->getClient()->createAuthUrl();
            return new RedirectResponse($authUrl);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to create authorization URL'], 500);
        }
    }

    /**
     * This PHP function handles a callback request by extracting an authorization code from the query
     * parameters, using it to obtain a JWT token via a Google OAuth2 service, and returning the token
     * or an error message in a JSON response.
     * 
     * @param ServerRequestInterface request The `handleCallback` function takes a
     * `ServerRequestInterface` object named `` as a parameter. This object represents an HTTP
     * request received by the server.
     * 
     * @return ResponseInterface The `handleCallback` function takes a `ServerRequestInterface` object
     * as a parameter, extracts the query parameters from the request, checks if the 'code' parameter
     * is present, and then attempts to handle the OAuth callback using the 'code' parameter with a
     * Google OAuth2 service.
     */
    public function handleCallback(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        if (!isset($queryParams['code'])) {
            return new JsonResponse(['error' => 'Authorization code missing'], 400);
        }

        $code = $queryParams['code'];
        try {
            $jwtToken = $this->googleOAuth2Service->handleOAuthCallback($code);
            return new JsonResponse(['token' => $jwtToken]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
