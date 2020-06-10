<?php


namespace Teamleader\Zoomroulette\Slack;


use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Teamleader\Zoomroulette\Slack\OauthProvider as OauthProviderAlias;

class SlackApiRepository
{
    /**
     * @var OauthProvider
     */
    private OauthProvider $oauthProvider;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(OauthProviderAlias $oauthProvider, LoggerInterface $logger)
    {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
    }


    public function post(string $url, string $body, AccessTokenInterface $accessToken) {
        $request = $this->oauthProvider->getAuthenticatedRequest(
            'POST',
            $url,
            $accessToken,
            [
                'body' => $body,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]
        );
        /** @var ResponseInterface $response */
        $createMeetingResponse = $this->oauthProvider->getResponse($request);
        return $createMeetingResponse;
    }


    public function postEphemeral(string $channelId, string $message, AccessTokenInterface $accessToken) : string
    {
        $payload = [
            'topic' => 'Zoom roulette baby!',
            'type' => 1,
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => true,
                'enforce_login' => false,
            ],
        ];
        $request = $this->oauthProvider->getAuthenticatedRequest(
            'POST',
            sprintf('https://api.zoom.us/v2/users/%s/meetings', $zoomUserId),
            $accessToken,
            [
                'body' => json_encode($payload),
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]
        );
        /** @var ResponseInterface $response */
        $createMeetingResponse = $this->oauthProvider->getResponse($request);
        $this->logger->debug("received response", ['headers' => $createMeetingResponse->getHeaders(), 'body' => $createMeetingResponse->getBody()->getContents()]);
        return $createMeetingResponse->getBody()->getContents();
    }

}