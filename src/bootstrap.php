<?php

use Defuse\Crypto\Key;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Marijnworks\Zoomroulette\Slack\OauthProvider as SlackOauthProvider;
use Marijnworks\Zoomroulette\Slack\SlackCommandAuthenticationMiddleware;
use Marijnworks\Zoomroulette\Zoom\OauthProvider as ZoomOauthProviderAlias;
use Marijnworks\Zoomroulette\Zoomroulette\EncryptionToolkit;
use Marijnworks\Zoomroulette\Zoomroulette\SessionMiddleware;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use SlimSession\Helper;

require_once __DIR__ . '/../vendor/autoload.php';

$container = new Container();

$container->addShared('settings', fn () => [
    'displayErrorDetails' => $_ENV['DISPLAY_ERROR_DETAILS'] === 'true',
]);

$container->addShared(SessionMiddleware::class, fn () => new SessionMiddleware([
    'name' => 'covfeferoulette',
    'autorefresh' => true,
    'lifetime' => '20 minutes',
]));

$container->addShared(SlackCommandAuthenticationMiddleware::class, fn () => new SlackCommandAuthenticationMiddleware(
    $_ENV['SLACK_SIGNINGSECRET'],
    $container->get(LoggerInterface::class)
));

// DBAL 4 removed the 'url' connection parameter; parse the DSN explicitly.
$container->addShared(Connection::class, fn () => DriverManager::getConnection(
    (new DsnParser(['mysql' => 'pdo_mysql']))->parse($_ENV['DATABASE_URL'])
));

// register the reflection container as a delegate to enable auto wiring
$container->delegate(new ReflectionContainer());

$container->addShared(ZoomOauthProviderAlias::class, fn () => new ZoomOauthProviderAlias([
    'clientId' => $_ENV['ZOOM_CLIENTID'],
    'clientSecret' => $_ENV['ZOOM_CLIENTSECRET'],
    'redirectUri' => $_ENV['ROOT_URL'] . '/auth/zoom',
    'urlAuthorize' => 'https://zoom.us/oauth/authorize',
    'urlAccessToken' => 'https://zoom.us/oauth/token',
    'urlResourceOwnerDetails' => 'https://api.zoom.us/v2/users/me',
]));

$container->addShared(Helper::class, fn () => new Helper());

$container->addShared(SlackOauthProvider::class, function () {
    return new SlackOauthProvider([
        'clientId' => $_ENV['SLACK_CLIENTID'],
        'clientSecret' => $_ENV['SLACK_CLIENTSECRET'],
        'redirectUri' => $_ENV['ROOT_URL'] . '/auth/slack',
        'urlAuthorize' => 'https://slack.com/oauth/v2/authorize',
        'urlAccessToken' => 'https://slack.com/api/oauth.v2.access',
        'urlResourceOwnerDetails' => 'https://slack.com/api/users.identity',
    ]);
});

// Set view in Container
$container->addShared(Twig::class, fn () => Twig::create(
    __DIR__ . '/../templates',
    [
        'cache' => __DIR__ . '/../templates/cache',
    ]
));

$container->addShared(EncryptionToolkit::class, fn () => new EncryptionToolkit(
    Key::loadFromAsciiSafeString($_ENV['CRYPTO_SECRET'])
));

$container->addShared(LoggerInterface::class, function () {
    $log = new Logger('zoomroulette');
    $log->pushHandler(new StreamHandler('php://stdout', Logger::toMonologLevel($_ENV['LOG_LEVEL'])));

    return $log;
});
