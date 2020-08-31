<?php

namespace Teamleader\Zoomroulette\Slack;

use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use SlimSession\Helper;
use Teamleader\Zoomroulette\Zoomroulette\User;
use Teamleader\Zoomroulette\Zoomroulette\UserNotFoundException;
use Teamleader\Zoomroulette\Zoomroulette\UserRepository;

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

    public function __construct(OauthProvider $oauthProvider, UserRepository $userRepository, LoggerInterface $logger)
    {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
        $this->userRepository = $userRepository;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        if (!isset($_GET['code'])) {
            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorizationUrl = $this->oauthProvider->getAuthorizationUrl([
                'scope' => 'chat:write commands',
            ]);

            $request->getAttribute('session')->set('oauth2state', $this->oauthProvider->getState());

            return $response->withHeader('Location', $authorizationUrl)->withStatus(302);
        }
        /** @var Helper $session */
        $session = $request->getAttribute('session');

        if (empty($_GET['state']) || $session->exists('oauht2state') && $_GET['state'] !== $session->get('oauth2state')) {
            if ($session->exists('oauht2state')) {
                $session->delete('oauth2state');
            }

            $response->getBody()->write('Something went wrong, try again <a href="/auth/slack">here</a>');

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
                $response->getBody()->write('All ok! Now be sure to also authenticate <a href="/auth/zoom">zoom</a>');

                return $response;
            } catch (UserNotFoundException $e) {
                $user = new User('slack', $accessToken->getValues()['authed_user']['id'], $accessToken);
                $user = $this->userRepository->add($user);
                $request->getAttribute('session')->set('userid', $user->getId());
                $response->getBody()->write('All ok! Now be sure to also authenticate <a href="/auth/zoom">zoom</a>');

                return $response;
            }
        } catch (IdentityProviderException $e) {
            $this->logger->error('Failed to get access token or user details', $e->getTrace());
            $response->getBody()->write('Something went wrong, try again <a href="/auth/slack">here</a>');

            return $response->withStatus(400, $e->getMessage());
        } catch (Exception $e) {
            $this->logger->error('Failed to get access token or user details', $e->getTrace());
            $response->getBody()->write('Something went wrong, try again <a href="/auth/slack">here</a>');

            return $response->withStatus(400, $e->getMessage());
        }
    }
}
