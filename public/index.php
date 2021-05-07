<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\Twig;
use Marijnworks\Zoomroulette\Slack\OauthRequestHandler as SlackOauthRequestHandler;
use Marijnworks\Zoomroulette\Slack\SlackCommandAuthenticationMiddleware;
use Marijnworks\Zoomroulette\Slack\SpinCommandHandler;
use Marijnworks\Zoomroulette\Slack\HelpCommandHandler;
use Marijnworks\Zoomroulette\Zoom\MeetingTestRequestHandler;
use Marijnworks\Zoomroulette\Zoom\OauthRequestHandler as ZoomOauthRequestHandler;
use Marijnworks\Zoomroulette\Zoomroulette\AuthenticationMiddleware;
use Marijnworks\Zoomroulette\Zoomroulette\ErrorHandler;
use Marijnworks\Zoomroulette\Zoomroulette\HtmlErrorRenderer;
use Marijnworks\Zoomroulette\Zoomroulette\SessionMiddleware;
use Marijnworks\Zoomroulette\Zoomroulette\JoinCallHandler;

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
$app->any('/devnull', function (Request $request, Response $response, $args) use ($container) {
	return $response;
});

$app->any('/support', function(Request $request, Response $response, $args) use ($container) {
	$twig = $container->get(Twig::class);
	$response->getBody()->write($twig->getEnvironment()->render('support.html', []));
	return $response;
});

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
   $group->post('/help', HelpCommandHandler::class);
})->add($container->get(SlackCommandAuthenticationMiddleware::class));


$app->run();
