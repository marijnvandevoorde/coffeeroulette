<?php

namespace Marijnworks\Zoomroulette\Zoomroulette;

use Slim\Error\Renderers\HtmlErrorRenderer as SlimHtmlErrorRenderer;
use Slim\Exception\HttpForbiddenException;
use Slim\Views\Twig;
use Throwable;

class HtmlErrorRenderer extends SlimHtmlErrorRenderer
{
    private Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        if ($displayErrorDetails) {
            return parent::__invoke($exception, $displayErrorDetails);
        }
        if ($exception instanceof HttpForbiddenException) {
            return $this->view->getEnvironment()->render('errors/default.html', [
                'message' => 'please authenticate through Slack before trying to link a Zoom account',
                'link' => [
                    'copy' => 'login',
                    'link' => 'slacklogin',
                ],
            ]);
        }

        return $this->view->getEnvironment()->render('errors/default.html', [
            'message' => 'unknown error',
        ]);
    }
}
