<?php

namespace Teamleader\Zoomroulette\Slack;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Teamleader\Zoomroulette\Zoom\OauthProvider as ZoomOauthProviderAlias;
use Teamleader\Zoomroulette\Zoom\ZoomApiRepository;
use Teamleader\Zoomroulette\Zoomroulette\Spin;
use Teamleader\Zoomroulette\Zoomroulette\SpinRepository;
use Teamleader\Zoomroulette\Zoomroulette\User;
use Teamleader\Zoomroulette\Zoomroulette\UserNotFoundException;
use Teamleader\Zoomroulette\Zoomroulette\UserRepository;

class SpinCommandHandler
{
    private OauthProvider $oauthProvider;

    private LoggerInterface $logger;

    private UserRepository $userRepository;

    private ZoomApiRepository $zoomApiRepository;

    private ZoomOauthProviderAlias $zoomOauthProvider;

    private SlackApiRepository $slackApiRepository;

    private SpinRepository $spinRepository;

    public function __construct(OauthProvider $oauthProvider, UserRepository $userRepository, SpinRepository $spinRepository, LoggerInterface $logger, ZoomApiRepository $zoomApiRepository, ZoomOauthProviderAlias $zoomOauthProvider, SlackApiRepository $slackApiRepository)
    {
        $this->oauthProvider = $oauthProvider;
        $this->logger = $logger;
        $this->userRepository = $userRepository;
        $this->zoomApiRepository = $zoomApiRepository;
        $this->zoomOauthProvider = $zoomOauthProvider;
        $this->slackApiRepository = $slackApiRepository;
        $this->spinRepository = $spinRepository;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $body = $request->getParsedBody();
        $this->logger->debug('slash command received', [
            'command' => $body['command'],
            'text' => $body['text'],
        ]);

        /** @var User $user */
        try {
            $user = $this->userRepository->findBySsoId('slack', $body['user_id']);
        } catch (UserNotFoundException $e) {
            // No user, tell the one who called zoom

            $privateBody = sprintf('{
                "blocks": [
                    {
                        "type": "section",
                        "text": {
                            "type": "mrkdwn",
                            "text": "Please authorize your account first. "
                        },
                        "accessory": {
                            "type": "button",
                            "text": {
                                "type": "plain_text",
                                "text": "Authorize"
                            },
                            "url": "%s"
                        }
                    }
                ]
            }', $_ENV['ROOT_URL'] . '/auth/slack');

            $response->getBody()->write($privateBody);

            return $response->withHeader('Content-type', 'application/json');
        }
        if ($user->getZoomAccessToken()->hasExpired()) {
            $newAccessToken = $this->zoomOauthProvider->getAccessToken('refresh_token', [
                'refresh_token' => $user->getZoomAccessToken()->getRefreshToken(),
            ]);
            $user->setZoomAccessToken($newAccessToken);
            $this->userRepository->update($user);
        }
        if ($user->getSsoAccessToken()->getExpires() && $user->getZoomAccessToken()->hasExpired()) {
            $newAccessToken = $this->oauthProvider->getAccessToken('refresh_token', [
                'refresh_token' => $user->getSsoAccessToken()->getRefreshToken(),
            ]);
            $user->setSsoAccessToken($newAccessToken);
            $this->userRepository->update($user);
        }
        $this->logger->debug('slash command for user', ['user' => $user]);
        $meeting = $this->zoomApiRepository->createMeeting($user->getZoomUserid(), $user->getZoomAccessToken());

        $privateBody = sprintf('{
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

        $spots = empty($body['text']) ? 1 : (intval($body['text']) ? intval($body['text']) : 1);

        $spin = new Spin($meeting->getJoinMeetingUrl(), $spots);
        $spin = $this->spinRepository->add($spin);

        $guestBody = sprintf(
            '{
                "response_type": "in_channel",
                "blocks": [
                    {
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
            }',
            $_ENV['ROOT_URL'] . '/join/ ' . $spin->getUuid()
        );

        $this->logger->debug('guestbody', ['body' => $guestBody]);

        $this->slackApiRepository->post($body['response_url'], $guestBody, $user->getSsoAccessToken());

        $this->logger->debug($meeting->getStartMeetingUrl());
        $this->logger->debug($meeting->getJoinMeetingUrl());

        $response->getBody()->write($privateBody);

        return $response->withHeader('Content-type', 'application/json');
    }
}
