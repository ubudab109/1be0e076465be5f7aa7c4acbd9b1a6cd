<?php

namespace Src\Services;

use Firebase\JWT\Key;
use Google_Client;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Google_Service_Oauth2;

class GoogleOauthService
{
    private $client;
    private $jwtSecretKey;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setClientId($_ENV['CLIENT_ID']);
        $this->client->setClientSecret($_ENV['CLIENT_SECRET']);
        $this->client->setRedirectUri($_ENV['REDIRECT_URI']);
        $this->client->setScopes(['email', 'profile']);
        $this->client->setAccessType('offline');
        $this->jwtSecretKey = $_ENV['JWT_SECRET'];
    }

    public function getClient()
    {
        return $this->client;
    }

    public function handleOAuthCallback($code)
    {
        $this->client->fetchAccessTokenWithAuthCode($code);

        $oauth2 = new Google_Service_Oauth2($this->client);
        $userInfo = $oauth2->userinfo->get();

        // Buat dan kembalikan JWT token
        return $this->createJwtToken($userInfo);
    }

    private function createJwtToken($userInfo)
    {
        $payload = [
            'iat' => time(),
            'exp' => time() + 3600,
            'sub' => $userInfo->id,
            'name' => $userInfo->name,
            'email' => $userInfo->email,
        ];

        return JWT::encode($payload, $this->jwtSecretKey, 'HS256');
    }

    public function getJwtSecretKey()
    {
        return $this->jwtSecretKey;
    }

    public function validateJwtToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecretKey, 'HS256'));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new \Exception('Token expired');
        } catch (\Exception $e) {
            throw new \Exception('Invalid token');
        }
    }
}
