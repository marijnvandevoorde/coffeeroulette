<?php

namespace Teamleader\Zoomroulette\Zoomroulette;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Views\Twig;
use SlimSession\Helper;

class AuthenticationMiddleware
{
    private ResponseFactoryInterface $responseFactory;

    private Twig $templateEngine;

    public function __construct(ResponseFactoryInterface $responseFactory, Twig $templateEngine)
    {
        $this->responseFactory = $responseFactory;
        $this->templateEngine = $templateEngine;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Helper $session */
        $session = $request->getAttribute('session');
        if (!$session->exists('userid')) {
            $response = $this->responseFactory->createResponse();

            $response->getBody()->write(
                $this->templateEngine->getEnvironment()->render('slackauth.html', ['error' => 'Please authorize your Slack account first and then setup your Zoom account.'])
            );

            return $response->withStatus(500);
        }

        return $handler->handle($request);
    }
}
