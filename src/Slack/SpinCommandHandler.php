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
    private OauthProvider $oauthProvider;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

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
        /**
         * {"args":[],
         * "body":
         * {
         * "token":"TESTTOKEN",
         * "team_id":"T00000001",
         * "team_domain":"exampleteam",
         * "channel_id":"D00000001",
         * "channel_name":"directmessage",
         * "user_id":"U00000001",
         * "user_name":"test.user",
         * "command":"/zoomroulette",
         * "text":"",
         * "response_url":"https://hooks.slack.com/commands/T00000001/1000000000002/TESTRESPONSESECRET2",
         * "trigger_id":"1000000000002.1000000000000.testtrigger2"}}
         */
        $this->logger->debug("slash command received", [
            'args' => $args,
            'isarray' => '' . is_array($request->getParsedBody()),
            'type' => get_class($request->getParsedBody()),
            'body' => $request->getParsedBody()
        ]);
        $this->logger->debug($request->getParsedBody()['command']);
    }
}
