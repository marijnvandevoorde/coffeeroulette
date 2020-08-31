<?php


namespace Teamleader\Zoomroulette\Zoomroulette;


use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Teamleader\Zoomroulette\Slack\OauthProvider;
use Teamleader\Zoomroulette\Slack\SlackOauthStorage;

class JoinCallHandler
{

    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var SpinRepository
     */
    private SpinRepository $spinRepository;

    public function __construct(SpinRepository $spinRepository,  LoggerInterface $logger) {
        $this->logger = $logger;
        $this->spinRepository = $spinRepository;
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response, $args)
    {
        try {

            $spin = $this->spinRepository->claimSpotByUuid(Uuid::fromString($args['id']));
            return $response->withHeader('Location', $spin->getJoinLink())->withStatus(302);
        } catch (SpinNotFoundException $e) {
            $response->getBody()->write("woops, too late! why not start your own spin using the /zoomroulette slack command?");
            return $response->withStatus(404);
        }
    }

}