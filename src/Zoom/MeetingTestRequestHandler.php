<?php


namespace Teamleader\Zoomroulette\Zoom;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class MeetingTestRequestHandler
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

    public function __construct(
        OauthProvider $oauthProvider,
        ZoomOauthStorage $zoomOauthStorage,
        LoggerInterface $logger
    ) {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
        $this->zoomOauthStorage = $zoomOauthStorage;
    }


    public function __invoke(RequestInterface $request, ResponseInterface $response, $args)
    {
        $accessToken = $this->zoomOauthStorage->getTokenById($args['user_id']);
        $payload = [
            "topic" => "Zoom roulette baby!",
            "type" => 1,
            "settings" => [
                "host_video" => true,
                "participant_video" => true,
                "join_before_host" => true,
                "enforce_login" => false
            ]
        ];
        $request = $this->oauthProvider->getAuthenticatedRequest(
            'POST',
            sprintf('https://api.zoom.us/v2/users/%s/meetings', $args['user_id']),
            $accessToken,
            [
                'body' => json_encode($payload),
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]
        );
        /** @var  $response */
        $createMeetingResponse = $this->oauthProvider->getParsedResponse($request);
        $this->logger->debug('created meeting', $createMeetingResponse);
        return $response;
    }
}