<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Teamleader\Zoomroulette\Slack\OauthRequestHandler as SlackOauthRequestHandler;
use Teamleader\Zoomroulette\Zoom\MeetingTestRequestHandler;
use Teamleader\Zoomroulette\Zoom\OauthRequestHandler as ZoomOauthRequestHandler;

require __DIR__ . '/../src/bootstrap.php';

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});

$app->group('/auth', function (RouteCollectorProxy $group) {
    $group->get('/zoom', ZoomOauthRequestHandler::class)->add(AuthenticationMiddleware::class);
    $group->get('/slack', SlackOauthRequestHandler::class);
    $group->get('/', function (Request $request, Response $response, $args) {

        var_dump($_SESSION);
        return $response;
    });

});
$app->group('/zoom', function (RouteCollectorProxy $group) {
    $group->get('/', ZoomOauthRequestHandler::class);
    $group->get('/meeting-test/{user_id}', MeetingTestRequestHandler::class);
});


$app->run();