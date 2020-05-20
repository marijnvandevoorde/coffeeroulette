<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\TwigMiddleware;
use Teamleader\Zoomroulette\Slack\OauthRequestHandler as SlackOauthRequestHandler;
use Teamleader\Zoomroulette\Slack\SpinCommandHandler;
use Teamleader\Zoomroulette\Zoom\MeetingTestRequestHandler;
use Teamleader\Zoomroulette\Zoom\OauthRequestHandler as ZoomOauthRequestHandler;
use Teamleader\Zoomroulette\Zoomroulette\AuthenticationMiddleware;
use Teamleader\Zoomroulette\Zoomroulette\ErrorHandler;
use Teamleader\Zoomroulette\Zoomroulette\HtmlErrorRenderer;
use Teamleader\Zoomroulette\Zoomroulette\SessionMiddleware;

require __DIR__ . '/../src/bootstrap.php';

AppFactory::setContainer($container);
$app = AppFactory::create();


// Add Routing Middleware
$app->addRoutingMiddleware();


$errorMiddleware = $app->addErrorMiddleware($container->get('settings')['displayErrorDetails'], true, true, $container->get(LoggerInterface::class));

if (!$container->get('settings')['displayErrorDetails']) {
// Get the default error handler and register my custom error renderer.
    $errorHandler = $errorMiddleware->getDefaultErrorHandler();
    $errorHandler->registerErrorRenderer('text/html', HtmlErrorRenderer::class);
}


$app->group('/auth', function (RouteCollectorProxy $group) {
    $group->get('/zoom', ZoomOauthRequestHandler::class)->add(AuthenticationMiddleware::class);
    $group->get('/slack', SlackOauthRequestHandler::class)->setName('slacklogin');
    $group->get('/', function (Request $request, Response $response, $args) {

        var_dump($_SESSION);
        return $response;
    });

})
    ->add($container->get(SessionMiddleware::class));

$app->group('/zoom', function (RouteCollectorProxy $group) {
   $group->get('/spin', SpinCommandHandler::class);
});



$app->add(TwigMiddleware::create($app, $container->get(\Slim\Views\Twig::class)));

$app->run();