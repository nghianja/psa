<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class SessionMiddleware implements Middleware
{
    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        // var_dump($_SERVER);
//        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            session_start();
            // add the session storage to your request as [READ-ONLY]
            $request = $request->withAttribute('session', $_SESSION);
//        }
        return $handler->handle($request);
    }
}