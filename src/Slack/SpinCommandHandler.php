<?php

namespace Teamleader\Zoomroulette\Slack;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Teamleader\Zoomroulette\Zoom\OauthProvider as ZoomOauthProviderAlias;
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
    /**
     * @var ZoomOauthProviderAlias
     */
    private ZoomOauthProviderAlias $zoomOauthProvider;
    /**
     * @var SlackApiRepository
     */
    private SlackApiRepository $slackApiRepository;

    public function __construct(OauthProvider $oauthProvider, UserRepository $userRepository,  LoggerInterface $logger, ZoomApiRepository $zoomApiRepository, ZoomOauthProviderAlias $zoomOauthProvider, SlackApiRepository $slackApiRepository)
    {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
        $this->userRepository = $userRepository;
        $this->zoomApiRepository = $zoomApiRepository;
        $this->zoomOauthProvider = $zoomOauthProvider;
        $this->slackApiRepository = $slackApiRepository;
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
        if ($user->getZoomAccessToken()->hasExpired()) {
            $newAccessToken = $this->zoomOauthProvider->getAccessToken('refresh_token', [
                'refresh_token' => $user->getZoomAccessToken()->getRefreshToken()
            ]);
            $user->setZoomAccessToken($newAccessToken);
            $this->userRepository->update($user);

        }
        if ($user->getSsoAccessToken()->getExpires() && $user->getZoomAccessToken()->hasExpired()) {
            $newAccessToken = $this->oauthProvider->getAccessToken('refresh_token', [
                'refresh_token' => $user->getSsoAccessToken()->getRefreshToken()
            ]);
            $user->setSsoAccessToken($newAccessToken);
            $this->userRepository->update($user);

        }
        $this->logger->debug('slash command for user', ['user' => $user]);
        $meeting = $this->zoomApiRepository->createMeeting($user->getZoomUserid(), $user->getZoomAccessToken());

        $body = sprintf('{
	"blocks": [
		{
			"type": "section",
			"text": {
				"type": "mrkdwn",
				"text": "The roulette is spinning! Don\'t leave your mystery date hanging"
			},
			"accessory": {
				"type": "button",
				"text": {
					"type": "plain_text",
					"text": "Start the meeting"
				},
				"url": "%s"
			}
		}
	]
}', $meeting->getStartMeetingUrl());

        $guestBody =  $body = sprintf('{
	"blocks": [
		{
            "response_type": "in_channel",
			"type": "section",
			"text": {
				"type": "mrkdwn",
				"text": "Ooooh, someone spun the zoom roulette! Act fast, think later!"
			},
			"accessory": {
				"type": "button",
				"text": {
					"type": "plain_text",
					"text": "Jump in!"
				},
				"url": "%s"
			}
		}
	]
}', $meeting->getJoinMeetingUrl());

        $this->slackApiRepository->post($body['response_url'], $guestBody, $user->getSsoAccessToken());

        $this->logger->debug($meeting->getStartMeetingUrl());
        $this->logger->debug($meeting->getJoinMeetingUrl());

        $response->getBody()->write($body);
        return $response->withHeader('Content-type', 'application/json');
    }
}
