<?php


namespace Slack;


use Monolog\Logger;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;
use Teamleader\Zoomroulette\Slack\SlackCommandAuthenticationMiddleware;

class SlackCommandAuthenticationMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $this->logger = new TestLogger();
        $this->middleware = new SlackCommandAuthenticationMiddleware(
            '8ae3230626097f6c48e88c478db78984',
            $this->logger
        );
    }
    /**
     * @test
     */
    public function signatureLogicTest() : void {
        $body = 'token=310AK8HlSecUl0YW8BmVk52V&team_id=T013WK2C7PE&team_domain=marikittens&channel_id=D013WKMBBV2&channel_name=directmessage&user_id=U013QDTBF5Y&user_name=marijn.vandevoorde&command=%2Fzoomroulette&text=&response_url=https%3A%2F%2Fhooks.slack.com%2Fcommands%2FT013WK2C7PE%2F1138119428243%2FIl5SanpQpemM0NvK71ORBYcA&trigger_id=1150533482337.1132648415796.c7429113fa2fd2ca258c964126e51581';
        $signature = [
            "v0=1fb9eb5d08755e68a295fa6231219f5af702711b272688a61d341f0b36cd48f1"
        ];
        $timestamp = [
            "1590063623"
        ];
        $secret = '8ae3230626097f6c48e88c478db78984';

        var_dump( 'sha256','v0:' . $timestamp[0] . ':' . $body);
        var_dump($secret);

        $hash = 'v0=' . hash_hmac('sha256','v0:' . $timestamp[0] . ':' . $body, $secret);
        $this->assertEquals($signature[0], $hash);
    }


}