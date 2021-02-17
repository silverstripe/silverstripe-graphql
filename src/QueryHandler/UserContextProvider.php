<?php


namespace SilverStripe\GraphQL\QueryHandler;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Controller;
use SilverStripe\GraphQL\Schema\Interfaces\ContextProvider;
use SilverStripe\Security\Member;

class UserContextProvider implements ContextProvider
{
    use Injectable;

    const KEY = 'currentUser';

    /**
     * @var Member|null
     */
    private $member;

    /**
     * UserContextProvider constructor.
     * @param Member|null $member
     */
    public function __construct(Member $member = null)
    {
        $this->member = $member;
    }

    /**
     * @param array $context
     * @return Member|null
     */
    public static function get(array $context): ?Member
    {
        return $context[self::KEY] ?? null;
    }

    /**
     * @return null[]|Member[]
     */
    public function provideContext(): array
    {
        return [
            self::KEY => $this->member,
        ];
    }
}
