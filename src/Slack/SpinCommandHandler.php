<?php

namespace Teamleader\Zoomroulette\Slack;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Teamleader\Zoomroulette\Zoom\ZoomApiRepository;
use Teamleader\Zoomroulette\Zoomroulette\User;
use Teamleader\Zoomroulette\Zoomroulette\UserRepository;

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
     * @var UserRepository
     */
    private UserRepository $userRepository;
    /**
     * @var ZoomApiRepository
     */
    private ZoomApiRepository $zoomApiRepository;

    public function __construct(OauthProvider $oauthProvider, UserRepository $userRepository,  LoggerInterface $logger, ZoomApiRepository $zoomApiRepository)
    {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
        $this->userRepository = $userRepository;
        $this->zoomApiRepository = $zoomApiRepository;
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
        $body = $request->getParsedBody();
        $this->logger->debug("slash command received", [
            'args' => $args,
            'body' => $body
        ]);
        /** @var User $user */
        $user = $this->userRepository->findBySsoId('slack', $body['user_id']);
        $this->logger->debug('slash command for user', ['user' => $user]);
        $zoomMeetingId = $this->zoomApiRepository->createMeeting($user->getZoomUserid(), $user->getZoomAccessToken());
        $this->logger->debug($zoomMeetingId);
        return $response->getBody()->write($zoomMeetingId);
    }
}
