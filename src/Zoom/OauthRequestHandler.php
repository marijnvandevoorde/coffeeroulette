<?php


namespace Teamleader\Zoomroulette\Zoom;


use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
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

    public function __construct(OauthProvider $oauthProvider, LoggerInterface $logger)
    {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
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

            // Redirect the user to the authorization URL.
            header('Location: ' . $authorizationUrl);
            exit;

            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {

            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }

            exit('Invalid state');

        } else {
            try {

                // Try to get an access token using the authorization code grant.
                $accessToken = $this->oauthProvider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);
                $data = [
                    'access_token' => $accessToken->getToken(),
                    'refresh_token' => $accessToken->getRefreshToken(),
                    'expires' => $accessToken->getExpires()
                ];
                $resourceOwner = $this->oauthProvider->getResourceOwner($accessToken);
                $data['user_id'] = $resourceOwner->getId();

                $tokenData = json_encode($accessToken);

                /** @var AccessToken $aToken */
                $aToken = json_decode($tokenData);


                $request = $this->oauthProvider->getAuthenticatedRequest(
                    'POST',
                    sprintf('https://api.zoom.us/v2/users/%s/meetings', $aToken->getResourceOwnerId()),
                    $aToken
                );
                /** @var  $response */
                $response = $this->oauthProvider->getParsedResponse($request);
                $this->logger->debug('created meeting', $response);

            } catch (IdentityProviderException $e) {
                $this->logger->error('Failed to get access token or user details', $e->getTrace());
            } catch (Exception $e) {
                $this->logger->error('Failed to get access token or user details', $e->getTrace());
            }
        }
    }

}