<?php

namespace SilverStripe\GraphQL\Tests\Auth;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Auth\Handler;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Tests\Fake\BrutalAuthenticatorFake;
use SilverStripe\GraphQL\Tests\Fake\PushoverAuthenticatorFake;
use SilverStripe\Security\Member;

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
        Config::inst()->nest();
        Config::inst()->update('SilverStripe\\GraphQL', 'authenticators', null);

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
        Config::inst()->update('SilverStripe\\GraphQL', 'authenticators', [
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
        Config::inst()->update('SilverStripe\\GraphQL', 'authenticators', [
            ['class' => PushoverAuthenticatorFake::class]
        ]);

        $result = $this->handler->getAuthenticator();
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
        Config::inst()->update('SilverStripe\\GraphQL', 'authenticators', [
            ['class' => 'stdClass']
        ]);

        $this->handler->getAuthenticator();
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
        Config::inst()->update('SilverStripe\\GraphQL', 'authenticators', $authenticators);

        $this->assertInstanceOf($expected, $this->handler->getAuthenticator());
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
     * {@inheritDoc}
     */
    public function tearDown()
    {
        Config::inst()->unnest();
        parent::tearDown();
    }
}
