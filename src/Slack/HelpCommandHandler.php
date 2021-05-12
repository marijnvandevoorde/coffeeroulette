<?php

namespace Marijnworks\Zoomroulette\Slack;

use Marijnworks\Zoomroulette\Zoom\OauthProvider as ZoomOauthProviderAlias;
use Marijnworks\Zoomroulette\Zoom\ZoomApiRepository;
use Marijnworks\Zoomroulette\Zoomroulette\SpinRepository;
use Marijnworks\Zoomroulette\Zoomroulette\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class HelpCommandHandler
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
}
