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

    /**
     * The getClient function returns the client property of the current object.
     * 
     * @return Google_Client The `getClient()` function is returning the value of the `` property of the
     * current object.
     */
    public function getClient(): Google_Client
    {
        return $this->client;
    }

    /**
     * The function handles OAuth callback by fetching access token with authorization code, retrieving
     * user information, and creating and returning a JWT token.
     * 
     * @param string $code The `` parameter in the `handleOAuthCallback` function is typically the
     * authorization code that is received from the OAuth provider after the user has successfully
     * authenticated and authorized the application. This code is then used to exchange for an access
     * token that allows the application to make authorized API requests on behalf of the
     * 
     * @return string The `handleOAuthCallback` function is returning the JWT token created using the user
     * information obtained from the OAuth2 service.
     */
    public function handleOAuthCallback($code): string
    {
        $this->client->fetchAccessTokenWithAuthCode($code);

        $oauth2 = new Google_Service_Oauth2($this->client);
        $userInfo = $oauth2->userinfo->get();

        // Buat dan kembalikan JWT token
        return $this->createJwtToken($userInfo);
    }

    /**
     * The function `createJwtToken` generates a JWT token with specified user information and
     * expiration time.
     * 
     * @param mixed $userInfo The `createJwtToken` function takes in a `` parameter, which is
     * expected to be an object containing at least the following properties:
     * 
     * @return string The `createJwtToken` function returns a JSON Web Token (JWT) encoded with the given
     * payload data using the HS256 algorithm.
     */
    private function createJwtToken(mixed $userInfo): string
    {
        $payload = [
            'iat' => time(),
            'exp' => time() + $_ENV['EXPIRY_TOKEN_IN_SECOND'] ?? 3600,
            'sub' => $userInfo->id,
            'name' => $userInfo->name,
            'email' => $userInfo->email,
        ];

        return JWT::encode($payload, $this->jwtSecretKey, 'HS256');
    }

    /**
     * The function `getJwtSecretKey` returns the JWT secret key.
     * 
     * @return string `jwtSecretKey` property is being returned by the `getJwtSecretKey` method.
     */
    public function getJwtSecretKey(): string
    {
        return $this->jwtSecretKey;
    }

    /**
     * The function `validateJwtToken` decodes a JWT token using a secret key and returns the decoded
     * data as an array, handling exceptions for expired or invalid tokens.
     * 
     * @param string $token The `validateJwtToken` function takes a JWT token as input and decodes it using the
     * `JWT::decode` method. If the token is successfully decoded, it is cast to an array and returned.
     * 
     * @return array An array containing the decoded JWT token data is being returned.
     */
    public function validateJwtToken($token): array
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
