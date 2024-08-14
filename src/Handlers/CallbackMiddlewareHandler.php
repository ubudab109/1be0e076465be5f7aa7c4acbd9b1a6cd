<?php

namespace Src\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CallbackMiddlewareHandler implements RequestHandlerInterface
{
    private $callback, $vars;

    public function __construct(callable $callback, array $vars = [])
    {
        $this->callback = $callback;
        $this->vars = $vars;
    }

    /**
     * The function takes a ServerRequestInterface object as a parameter and calls a callback function
     * with the request object as an argument, returning the response.
     * 
     * @param ServerRequestInterface request The `` parameter in the `handle` function is of
     * type `ServerRequestInterface`. This parameter represents the HTTP request received by the server
     * and contains information such as the request method, headers, and body. It is used by the
     * function to process the incoming request and generate a response.
     * 
     * @return ResponseInterface The `handle` function is returning the result of calling the callback
     * function stored in the class property `` with the `` parameter passed as an
     * argument.
     */
    public function handle(ServerRequestInterface $request = null): ResponseInterface
    {
        return call_user_func_array($this->callback, [$request, $this->vars]);
    }
}
