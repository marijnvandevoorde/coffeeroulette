<?php

use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Teamleader\Zoomroulette\Zoom\OauthProvider;
use Teamleader\Zoomroulette\Zoom\ZoomOauthStorage;

require_once __DIR__ . '/../vendor/autoload.php';

$container = new Container();

// register the reflection container as a delegate to enable auto wiring
$container->delegate(new ReflectionContainer());

$container->share( OauthProvider::class, function () use ($container) {
    return new OauthProvider([
        'clientId' => getenv('ZOOM_CLIENTID'),
        'clientSecret' => getenv('ZOOM_CLIENTSECRET'),
        'redirectUri' => getenv('ROOT_URL') . '/zoom/oauth-redirect',
        'urlAuthorize' => 'https://zoom.us/oauth/authorize',
        'urlAccessToken' => 'https://zoom.us/oauth/token',
        'urlResourceOwnerDetails' => 'https://api.zoom.us/v2/users/me'
    ]);
});


$container->share(LoggerInterface::class, function () {
    $log = new Logger('zoomroulette');
    $log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
    return $log;
});

$container->share(ZoomOauthStorage::class, function () {
   return new ZoomOauthStorage(__DIR__ . '/../storage/');
});