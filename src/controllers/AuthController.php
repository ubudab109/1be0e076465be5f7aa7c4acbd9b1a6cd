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

    public function login()
    {
        try {
            $authUrl = $this->googleOAuth2Service->getClient()->createAuthUrl();
            return new RedirectResponse($authUrl);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to create authorization URL'], 500);
        }
    }

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
