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

    private SlackOauthStorage $zoomOauthStorage;

    public function __construct(
        OauthProvider $oauthProvider,
        SlackOauthStorage $zoomOauthStorage,
        LoggerInterface $logger
    ) {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
        $this->zoomOauthStorage = $zoomOauthStorage;
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->logger->debug('created meeting', $createMeetingResponse);

        return $response;
    }
}
