<?php

namespace Exonet\Api;

use Exonet\Api\Auth\PersonalAccessToken;
use Exonet\Api\Exceptions\AuthenticationException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Because of testing singleton functions, this test are executed in separate processes. This guarantees that a fresh
 * instance is created at the start of each test.
 *
 * @runTestsInSeparateProcesses
 *
 * @internal
 */
class ClientTest extends TestCase
{
    public function testSingletonViaConstructor()
    {
        $logger = \Mockery::mock(LoggerInterface::class);
        $auth = \Mockery::mock(PersonalAccessToken::class);

        $client = new Client($auth);
        $client->setLogger($logger);

        $this->assertSame($logger, Client::getInstance()->log());
        $this->assertSame($auth, Client::getInstance()->getAuth());
    }

    public function testSingletonViaGetInstance()
    {
        $logger = \Mockery::mock(LoggerInterface::class);
        $auth = \Mockery::mock(PersonalAccessToken::class);

        Client::getInstance()->setAuth($auth);
        Client::getInstance()->setLogger($logger);

        $this->assertSame($logger, Client::getInstance()->log());
        $this->assertSame($auth, Client::getInstance()->getAuth());
    }

    public function testAuth()
    {
        $auth = new PersonalAccessToken('token1');
        $auth2 = new PersonalAccessToken('token2');

        $client = new Client($auth);

        $this->assertSame('token1', $client->getAuth()->getToken());

        $client->setAuth($auth2);
        $this->assertSame('token2', $client->getAuth()->getToken());
    }

    public function testAuthNotSet()
    {
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->withArgs(['No authentication method set.']);

        $client = new Client();
        $client->setLogger($logger);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No authentication method set.');

        $client->getAuth()->getToken();
    }

    public function testGetApiUrlDefault()
    {
        $client = new Client();
        $this->assertSame('https://api.exonet.nl/', $client->getApiUrl());
    }

    public function testGetApiUrlGiven()
    {
        $client = new Client(null, Client::API_TEST_URL);
        $this->assertSame('https://test-api.exonet.nl/', $client->getApiUrl());
    }

    public function testSetApiUrl()
    {
        $client = new Client();
        $client->setApiUrl('https://unit-test.example.com');
        $this->assertSame('https://unit-test.example.com/', $client->getApiUrl());
    }

    public function testLogger()
    {
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->withArgs(['Test line']);

        $client = new Client();

        // Test that if no logger is set, calling it won't result in an error.
        $client->log()->info('Nullable test line');

        $client->setLogger($logger);
        $client->log()->info('Test line');

        $this->assertSame($logger, $client->log());
    }

    public function testResource()
    {
        $client = new Client();

        $this->assertInstanceOf(Request::class, $client->resource('/test'));
    }
}
