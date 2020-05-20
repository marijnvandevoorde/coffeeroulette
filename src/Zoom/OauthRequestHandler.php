<?php

namespace Teamleader\Zoomroulette\Zoom;

use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

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
     * @var ZoomOauthStorage
     */
    private ZoomOauthStorage $zoomOauthStorage;

    public function __construct(OauthProvider $oauthProvider, ZoomOauthStorage $zoomOauthStorage, LoggerInterface $logger)
    {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
        $this->zoomOauthStorage = $zoomOauthStorage;
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (!isset($_GET['code'])) {
            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorizationUrl = $this->oauthProvider->getAuthorizationUrl();

            // Get the state generated for you and store it to the session.
            $_SESSION['oauth2state'] = $this->oauthProvider->getState();

            return $response->withHeader('Location', $authorizationUrl);
        }

        if (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }

            return $response->withStatus(400, 'Invalid state');
        }

        try {
            // Try to get an access token using the authorization code grant.
            $accessToken = $this->oauthProvider->getAccessToken('authorization_code', [
                'code' => $_GET['code'],
            ]);
            $owner = $this->oauthProvider->getResourceOwner($accessToken);
            $this->zoomOauthStorage->save($owner->getId(), $accessToken);

            $response->getBody()->write('All ok!');

            return $response;
        } catch (IdentityProviderException $e) {
            $this->logger->error('Failed to get access token or user details', $e->getTrace());
        } catch (Exception $e) {
            $this->logger->error('Failed to get access token or user details', $e->getTrace());
        }
    }
}
