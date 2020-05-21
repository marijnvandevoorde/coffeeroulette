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
         * "token":"310AK8HlSecUl0YW8BmVk52V",
         * "team_id":"T013WK2C7PE",
         * "team_domain":"marikittens",
         * "channel_id":"D013WKMBBV2",
         * "channel_name":"directmessage",
         * "user_id":"U013QDTBF5Y",
         * "user_name":"marijn.vandevoorde",
         * "command":"/zoomroulette",
         * "text":"",
         * "response_url":"https://hooks.slack.com/commands/T013WK2C7PE/1137744844515/N8OoNCLe7Wzila1epxcIAHRT",
         * "trigger_id":"1137535912434.1132648415796.5af99977fc98a807032f91cc2f5e12a2"}}
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
