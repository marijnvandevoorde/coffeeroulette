<?php

namespace Teamleader\Zoomroulette\Zoom;

use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ZoomApiRepository
{
    private OauthProvider $oauthProvider;

    private LoggerInterface $logger;

    public function __construct(OauthProvider $oauthProvider, LoggerInterface $logger)
    {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
    }

    public function getMe(AccessTokenInterface $accessToken) {
        $request = $this->oauthProvider->getAuthenticatedRequest(
            'GET',
            sprintf('https://api.zoom.us/v2/users/me'),
            $accessToken,

        );
        return $this->oauthProvider->getResponse($request);
    }

    public function createMeeting(string $zoomUserId, AccessTokenInterface $accessToken): ZoomMeeting
    {
        $this->logger->debug("create meeting or at least try", ['token' => $accessToken->jsonSerialize()]);
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
            'https://api.zoom.us/v2/users/me/meetings',
            $accessToken,
            [
                'body' => json_encode($payload),
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]
        );
        try {
            /** @var ResponseInterface $createMeetingResponse */
            $createMeetingResponse = $this->oauthProvider->getResponse($request);
            $data = json_decode($createMeetingResponse->getBody()->getContents(), true);
        } catch (\Exception $e) {
            $this->logger->error('call failed', ['error' => $e->getMessage()]);

        }

        return new ZoomMeeting(
            $data['start_url'],
            $data['join_url']
        );
    }
}
