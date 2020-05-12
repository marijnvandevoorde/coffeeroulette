<?php

use League\Container\Container;
use League\Container\ReflectionContainer;
use Teamleader\Zoomroulette\Zoom\OauthProvider;

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