<?php

namespace Teamleader\Zoomroulette\Zoomroulette;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use SlimSession\Helper;

class AuthenticationMiddleware
{

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Helper $session */
        $session = $request->getAttribute('session');
        if (!$session->exists('userid')) {
            throw new HttpForbiddenException($request, 'Please authorize via slack first');
        }

        return $handler->handle($request);
    }
}
