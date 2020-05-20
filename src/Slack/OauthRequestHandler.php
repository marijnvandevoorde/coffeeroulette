<?php

namespace Teamleader\Zoomroulette\Slack;

use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
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

    /**
     * @var SlackOauthStorage
     */
    private SlackOauthStorage $slackOauthStorage;

    public function __construct(OauthProvider $oauthProvider, SlackOauthStorage $slackOauthStorage,  LoggerInterface $logger)
    {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
        $this->zoomOauthStorage = $slackOauthStorage;
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

            return $response->withHeader('Location', $authorizationUrl);
        }
        /** @var Helper $session */
        $session =  $request->getAttribute('session');

        if (empty($_GET['state']) || $session->exists('oauht2state') && $_GET['state'] !== $session->get('oauth2state')) {
            if ($session->exists('oauht2state')) {
                $session->delete('oauth2state');
            }

            return $response->withStatus(400, 'Invalid state');
        }

        try {
            // Try to get an access token using the authorization code grant.
            $accessToken = $this->oauthProvider->getAccessToken('authorization_code', [
                'code' => $_GET['code'],
            ]);

            $this->zoomOauthStorage->save($accessToken->getValues()['authed_user']['id'], $accessToken);
            $request->getAttribute('session')->set('userid', $accessToken->getValues()['authed_user']['id']);

            $response->getBody()->write('All ok!');

            return $response;
        } catch (IdentityProviderException $e) {
            $this->logger->error('Failed to get access token or user details', $e->getTrace());

            return $response->withStatus(400, $e->getMessage());
        } catch (Exception $e) {
            $this->logger->error('Failed to get access token or user details', $e->getTrace());

            return $response->withStatus(400, $e->getMessage());
        }
    }
}
