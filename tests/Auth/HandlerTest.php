<?php

namespace SilverStripe\GraphQL\Tests\Auth;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Auth\Handler;
use SilverStripe\GraphQL\Tests\Fake\BrutalAuthenticatorFake;
use SilverStripe\GraphQL\Tests\Fake\FalsyAuthenticatorFake;
use SilverStripe\GraphQL\Tests\Fake\PushoverAuthenticatorFake;

/**
 * @package silverstripe-graphql
 */
class HandlerTest extends SapphireTest
{
    /**
     * @var Handler
     */
    protected $handler;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        Handler::config()->remove('authenticators');
        $this->handler = new Handler;
    }

    /**
     * Ensure that nothing is done when no authenticators are configured
     */
    public function testRequireAuthenticationReturnsFalseWhenNoneAreConfigured()
    {
        $this->assertFalse($this->handler->requireAuthentication(new HTTPRequest('GET', '/')));
    }

    /**
     * Ensure that a successfully authenticated Member is returned by the Handler
     */
    public function testRequireAuthenticationReturnsMember()
    {
        Handler::config()->update('authenticators', [
            ['class' => PushoverAuthenticatorFake::class]
        ]);

        $member = $this->handler->requireAuthentication(new HTTPRequest('GET', '/'));
        $this->assertSame('john@example.com', $member->Email);
    }

    /**
     * Ensure that an authenticator is returned when configured correctly
     */
    public function testGetAuthenticator()
    {
        Handler::config()->update('authenticators', [
            ['class' => PushoverAuthenticatorFake::class]
        ]);

        $result = $this->handler->getAuthenticator(new HTTPRequest('GET', '/'));
        $this->assertInstanceOf(PushoverAuthenticatorFake::class, $result);
    }

    /**
     * Test that an exception is thrown if an authenticator is configured that doesn't implement the interface
     *
     * @expectedException \SilverStripe\ORM\ValidationException
     * @expectedExceptionMessage stdClass must implement SilverStripe\GraphQL\Auth\AuthenticatorInterface!
     */
    public function testExceptionThrownWhenAuthenticatorDoesNotImplementAuthenticatorInterface()
    {
        Handler::config()->update('authenticators', [
            ['class' => 'stdClass']
        ]);

        $this->handler->getAuthenticator(new HTTPRequest('GET', '/'));
    }

    /**
     * Test that authenticators can be prioritised and that priority is given a default value if not provided
     *
     * @param array  $authenticators
     * @param string $expected
     * @dataProvider prioritisedAuthenticatorProvider
     */
    public function testAuthenticatorsCanBePrioritised($authenticators, $expected)
    {
        Handler::config()->update('authenticators', $authenticators);

        $this->assertInstanceOf($expected, $this->handler->getAuthenticator(new HTTPRequest('GET', '/')));
    }

    /**
     * @return array
     */
    public function prioritisedAuthenticatorProvider()
    {
        return [
            [
                [
                    ['class' => PushoverAuthenticatorFake::class, 'priority' => 10],
                    ['class' => BrutalAuthenticatorFake::class, 'priority' => 100]
                ],
                BrutalAuthenticatorFake::class
            ],
            [
                [
                    ['class' => PushoverAuthenticatorFake::class, 'priority' => 100],
                    ['class' => BrutalAuthenticatorFake::class, 'priority' => 10]
                ],
                PushoverAuthenticatorFake::class
            ],
            [
                [
                    ['class' => PushoverAuthenticatorFake::class],
                    ['class' => BrutalAuthenticatorFake::class, 'priority' => 5]
                ],
                PushoverAuthenticatorFake::class
            ],
            [
                [
                    ['class' => PushoverAuthenticatorFake::class, 'priority' => 5],
                    ['class' => BrutalAuthenticatorFake::class]
                ],
                BrutalAuthenticatorFake::class
            ]
        ];
    }

    /**
     * Ensure that an failed authentication attempt throws an exception
     *
     * @expectedException \SilverStripe\ORM\ValidationException
     * @expectedExceptionMessage Never!
     */
    public function testFailedAuthenticationThrowsException()
    {
        Handler::config()->update('authenticators', [
            ['class' => BrutalAuthenticatorFake::class]
        ]);

        $this->handler->requireAuthentication(new HTTPRequest('/', 'GET'));
    }

    /**
     * Ensure that when a falsy value is returned from an authenticator (when it should throw
     * an exception on failure) that a sensible default message is used in a ValidationException
     * instead.
     *
     * @expectedException \SilverStripe\ORM\ValidationException
     * @expectedExceptionMessage Authentication failed.
     */
    public function testFailedAuthenticationWithFalsyReturnValueThrowsDefaultException()
    {
        Handler::config()->update('authenticators', [
            ['class' => FalsyAuthenticatorFake::class]
        ]);

        $this->handler->requireAuthentication(new HTTPRequest('/', 'GET'));
    }
}
