<?php

namespace Marijnworks\Zoomroulette\Slack;

use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Marijnworks\Zoomroulette\Slack\OauthProvider as OauthProviderAlias;

class SlackApiRepository
{
    private OauthProvider $oauthProvider;

    private LoggerInterface $logger;

    public function __construct(OauthProviderAlias $oauthProvider, LoggerInterface $logger)
    {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
    }

    public function post(string $url, string $body, AccessTokenInterface $accessToken)
    {
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
        /** @var ResponseInterface $createMeetingResponse */
        $createMeetingResponse = $this->oauthProvider->getResponse($request);

        return $createMeetingResponse;
    }

    public function postEphemeral(string $channelId, string $message, AccessTokenInterface $accessToken): string
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
            sprintf('https://api.zoom.us/v2/users/%s/meetings', $channelId),
            $accessToken,
            [
                'body' => json_encode($payload),
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]
        );
        /** @var ResponseInterface $createMeetingResponse */
        $createMeetingResponse = $this->oauthProvider->getResponse($request);

        return $createMeetingResponse->getBody()->getContents();
    }
}
