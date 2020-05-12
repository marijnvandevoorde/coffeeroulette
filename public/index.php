<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Teamleader\Zoomroulette\Zoom\OauthRequestHandler;

require __DIR__ . '/../src/bootstrap.php';

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});
$app->group('/zoom', function (RouteCollectorProxy $group) {
    $group->get('/oauth-redirect', OauthRequestHandler::class);
});


$app->run();