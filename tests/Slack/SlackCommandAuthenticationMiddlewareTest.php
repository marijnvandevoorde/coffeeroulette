<?php

namespace Slack;

use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Teamleader\Zoomroulette\Slack\SlackCommandAuthenticationMiddleware;

class SlackCommandAuthenticationMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $this->logger = new TestLogger();
        $this->middleware = new SlackCommandAuthenticationMiddleware(
            'test-signing-secret',
            $this->logger
        );
    }

    /**
     * @test
     */
    public function signatureLogicTest(): void
    {
        $body = 'token=TESTTOKEN&team_id=T00000001&team_domain=exampleteam&channel_id=D00000001&channel_name=directmessage&user_id=U00000001&user_name=test.user&command=%2Fzoomroulette&text=&response_url=https%3A%2F%2Fhooks.slack.com%2Fcommands%2FT00000001%2F1000000000001%2FTESTRESPONSESECRET1&trigger_id=1000000000001.1000000000000.testtrigger1';
        $signature = [
            'v0=b86f1688db0bf6aa425e1f86b5c9db27a12a623004be4148a306547e48b71fa5',
        ];
        $timestamp = [
            '1590063623',
        ];
        $secret = 'test-signing-secret';

        var_dump('sha256', 'v0:' . $timestamp[0] . ':' . $body);
        var_dump($secret);

        $hash = 'v0=' . hash_hmac('sha256', 'v0:' . $timestamp[0] . ':' . $body, $secret);
        $this->assertEquals($signature[0], $hash);
    }
}
