<?php
require __DIR__ . '/../config/database.php';

use FastRoute\RouteCollector;
use Src\Controllers\AuthController;
use Src\Controllers\EmailController;
use Src\Middleware\AuthMiddleware;
use Src\Repositories\EmailRepository;
use Src\Services\GoogleOauthService;

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addGroup('/api', function (RouteCollector $r) {
        $r->addRoute('GET', '/emails', [new EmailController(new EmailRepository(getPgConnection())), 'getEmails', AuthMiddleware::class]);
        $r->addRoute('GET', '/login', [new AuthController(new GoogleOauthService()), 'login']);
        $r->addRoute('GET', '/auth-callback', [new AuthController(new GoogleOauthService()), 'handleCallback']);
    });
});
