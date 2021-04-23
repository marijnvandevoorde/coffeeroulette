<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\Twig;
use Teamleader\Zoomroulette\Slack\OauthRequestHandler as SlackOauthRequestHandler;
use Teamleader\Zoomroulette\Slack\SlackCommandAuthenticationMiddleware;
use Teamleader\Zoomroulette\Slack\SpinCommandHandler;
use Teamleader\Zoomroulette\Zoom\MeetingTestRequestHandler;
use Teamleader\Zoomroulette\Zoom\OauthRequestHandler as ZoomOauthRequestHandler;
use Teamleader\Zoomroulette\Zoomroulette\AuthenticationMiddleware;
use Teamleader\Zoomroulette\Zoomroulette\ErrorHandler;
use Teamleader\Zoomroulette\Zoomroulette\HtmlErrorRenderer;
use Teamleader\Zoomroulette\Zoomroulette\SessionMiddleware;
use Teamleader\Zoomroulette\Zoomroulette\JoinCallHandler;

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

$home = function (Request $request, Response $response, $args) use ($container) {
    /** @var Twig $twig */
    $twig = $container->get(Twig::class);
    $response->getBody()->write($twig->getEnvironment()->render('landing.html', []));
    return $response;

}; //->add(TwigMiddleware::create($app, $container->get(\Slim\Views\Twig::class)));

$app->any('/', $home);

$app->any('/help.html', $home);

$app->get('/support', 'https://github.com/mrnnn/coffeeroulette/issues');

$app->get('/join/{id}', JoinCallHandler::class);


$app->group('/auth', function (RouteCollectorProxy $group) use ($container) {
    $group->get('/zoom', ZoomOauthRequestHandler::class)->add(
        new AuthenticationMiddleware($group->getResponseFactory(), $container->get(Twig::class))
    );
    $group->get('/slack', SlackOauthRequestHandler::class)->setName('slacklogin');
    $group->get('/', function (Request $request, Response $response, $args) {
        return $response;
    });

})
    ->add($container->get(SessionMiddleware::class));

$app->group('/slack', function (RouteCollectorProxy $group) {
   $group->post('/spin', SpinCommandHandler::class);
})->add($container->get(SlackCommandAuthenticationMiddleware::class));


$app->run();
