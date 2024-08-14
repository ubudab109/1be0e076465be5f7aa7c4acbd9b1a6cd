<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/routes/route.php'; // Correct the path to your routes file

use Dotenv\Dotenv;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response\JsonResponse;
use FastRoute\Dispatcher;
use Laminas\Diactoros\Response\RedirectResponse;
use Src\Handlers\CallbackMiddlewareHandler;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

if ($_ENV['PHP_ENV'] == 'development' && php_sapi_name() !== 'cli-server') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'This application must be run using the built-in PHP server. Please run this application with php -S localhost:8000 -t src']);
    exit;
}

// Create request
$request = ServerRequestFactory::fromGlobals();

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Dispatch request
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        $response = new JsonResponse(['status' => 'error', 'message' => 'Not Found'], 404);
        break;
    case Dispatcher::METHOD_NOT_ALLOWED:
        $response = new JsonResponse(['status' => 'error', 'message' => 'Method Not Allowed'], 405);
        break;
    case Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_array($handler)) {
            $controller = $handler[0];
            $method = $handler[1];

            if (is_object($controller) && method_exists($controller, $method)) {
                $callback = [$controller, $method];

                if (isset($handler[2])) {
                    // Middleware handling
                    $middlewareClass = $handler[2];
                    $middleware = new $middlewareClass();

                    // Create CallbackHandler instance
                    $callbackHandler = new CallbackMiddlewareHandler($callback);

                    // Process middleware
                    $response = $middleware->process($request, $callbackHandler);
                } else {
                    // No middleware, directly call the handler
                    $response = call_user_func_array($callback, [$request, $vars]);
                }
            } else {
                $response = new JsonResponse(['status' => 'error', 'message' => 'Invalid handler'], 500);
            }
        } else {
            $response = new JsonResponse(['status' => 'error', 'message' => 'Invalid handler format'], 500);
        }
        break;
}
// Handle RedirectResponse
if ($response instanceof RedirectResponse) {
    header('Location: ' . $response->getHeaderLine('Location'));
    exit;
} else {
    // Set headers and output response
    header('Content-Type: application/json');
    http_response_code($response->getStatusCode());
    echo $response->getBody();
}

