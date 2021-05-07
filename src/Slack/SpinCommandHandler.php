<?php

namespace Marijnworks\Zoomroulette\Slack;

use Marijnworks\Zoomroulette\Zoom\CouldNotCreateMeetingException;
use Marijnworks\Zoomroulette\Zoom\OauthProvider as ZoomOauthProviderAlias;
use Marijnworks\Zoomroulette\Zoom\ZoomApiRepository;
use Marijnworks\Zoomroulette\Zoomroulette\Spin;
use Marijnworks\Zoomroulette\Zoomroulette\SpinRepository;
use Marijnworks\Zoomroulette\Zoomroulette\User;
use Marijnworks\Zoomroulette\Zoomroulette\UserNotFoundException;
use Marijnworks\Zoomroulette\Zoomroulette\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

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

        if (!empty($body['text']) && $body['text'] === 'help') {
            $response->getBody()->write('{
                "blocks": [
                    {
                        "type": "section",
                        "text": {
                            "type": "mrkdwn",
				    "text": "Grab a coffee with a not-so-stranger at work.

Coffee roulette will spin up a Zoom meeting and post a link to it on your behalf. Only the first person to click the link will be able to join your meeting, unless you add a number to the command to allow some more people to your coffee table! 

Just try it by calling the `/coffeeroulette` command now!

For more information, visit <https://coffeeroulette.madewithlove.com|coffeeroulette.madewithlove.com>"
                        }
                    }
                ]
            }');

            return $response->withHeader('Content-type', 'application/json');
        }

        try {
            /** @var User $user */
            $user = $this->userRepository->findBySsoId('slack', $body['user_id']);
            if (!$user->getZoomAccessToken()) {
                throw new UserNotFoundException('no Zoom access token');
            }
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

        try {
            $meeting = $this->zoomApiRepository->createMeeting($user->getZoomUserid(), $user->getZoomAccessToken());
        } catch (CouldNotCreateMeetingException $e) {
            $response->getBody()->write('{
                "blocks": [
                    {
                        "type": "section",
                        "text": {
                            "type": "mrkdwn",
                            "text": "That didn\'t work quite as planned. Coffee Roulette was unable to create a meeting. Try again maybe?"
                        }
                    }
                ]
            }');

            return $response->withHeader('Content-type', 'application/json');
        }

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
                            "text": "Ooooh, someone spun the coffee roulette! Act fast, think later!"
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
            $_ENV['ROOT_URL'] . '/join/' . $spin->getUuid()
        );

        $this->slackApiRepository->post($body['response_url'], $guestBody, $user->getSsoAccessToken());

        $response->getBody()->write($privateBody);

        return $response->withHeader('Content-type', 'application/json');
    }
}
