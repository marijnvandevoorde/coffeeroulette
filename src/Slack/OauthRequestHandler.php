<?php

namespace Marijnworks\Zoomroulette\Slack;

use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Marijnworks\Zoomroulette\Zoomroulette\User;
use Marijnworks\Zoomroulette\Zoomroulette\UserNotFoundException;
use Marijnworks\Zoomroulette\Zoomroulette\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use SlimSession\Helper;

class OauthRequestHandler
{
    /**
     * @var OauthProvider
     */
    private $oauthProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private UserRepository $userRepository;

    private Twig $templateEngine;

    public function __construct(OauthProvider $oauthProvider, UserRepository $userRepository, LoggerInterface $logger, Twig $templateEngine)
    {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
        $this->userRepository = $userRepository;
        $this->templateEngine = $templateEngine;
    }

    /**
     * @param array<string,string> $args
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (!isset($_GET['code'])) {
            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorizationUrl = $this->oauthProvider->getAuthorizationUrl([
                'scope' => ['commands'],
            ]);

            $request->getAttribute('session')->set('oauth2state', $this->oauthProvider->getState());

            return $response->withHeader('Location', $authorizationUrl)->withStatus(302);
        }
        /** @var Helper<string> $session */
        $session = $request->getAttribute('session');

        if (empty($_GET['state']) || $session->exists('oauht2state') && $_GET['state'] !== $session->get('oauth2state')) {
            if ($session->exists('oauht2state')) {
                $session->delete('oauth2state');
            }

            $response->getBody()->write(
                $this->templateEngine->getEnvironment()->render('slackauth.html', ['error' => 'Something went wrong, please try again'])
            );

            return $response->withStatus(400, 'Invalid state');
        }

        try {
            // Try to get an access token using the authorization code grant.
            $accessToken = $this->oauthProvider->getAccessToken('authorization_code', [
                'code' => $_GET['code'],
            ]);

            try {
                $user = $this->userRepository->findBySsoId('slack', $accessToken->getValues()['authed_user']['id']);
                $user->setSsoAccessToken($accessToken);
                $this->userRepository->update($user);
                $request->getAttribute('session')->set('userid', $user->getId());

                $response->getBody()->write(
                    $this->templateEngine->getEnvironment()->render('zoomauth.html')
                );

                return $response->withStatus(200);
            } catch (UserNotFoundException $e) {
                $user = new User('slack', $accessToken->getValues()['authed_user']['id'], $accessToken);
                $user = $this->userRepository->add($user);
                $request->getAttribute('session')->set('userid', $user->getId());

                $response->getBody()->write(
                    $this->templateEngine->getEnvironment()->render('zoomauth.html')
                );

                return $response->withStatus(200);
            }
        } catch (IdentityProviderException $e) {
            $this->logger->error('Failed to get access token or user details', $e->getTrace());

            $response->getBody()->write(
                $this->templateEngine->getEnvironment()->render('slackauth.html', ['error' => 'Something went wrong, please try again'])
            );

            return $response->withStatus(400, $e->getMessage());
        } catch (Exception $e) {
            $this->logger->error('Failed to get access token or user details', $e->getTrace());

            $response->getBody()->write(
                $this->templateEngine->getEnvironment()->render('slackauth.html', ['error' => 'Something went wrong, please try again'])
            );

            return $response->withStatus(400, $e->getMessage());
        }
    }
}
