<?php

namespace Teamleader\Zoomroulette\Slack;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class SpinCommandHandler
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
        $this->slackOauthStorage = $slackOauthStorage;
    }


    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $this->logger->debug("slash command received", [
            'args' => $args,
            'isarray' => '' . is_array($request->getParsedBody()),
            'type' => get_class($request->getParsedBody()),
            'body' => $request->getParsedBody()
        ]);
    }
}
