<?php


namespace SilverStripe\GraphQL\QueryHandler;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Interfaces\ContextProvider;

class TokenContextProvider implements ContextProvider
{
    use Injectable;

    const KEY = 'token';

    private string $token;

    /**
     * TokenContextProvider constructor.
     * @param string $token
     */
    public function __construct(string $token = '')
    {
        $this->token = $token;
    }

    /**
     * @param array $context
     * @return string
     */
    public static function get(array $context): ?string
    {
        return $context[self::KEY] ?? null;
    }

    /**
     * @return string[]
     */
    public function provideContext(): array
    {
        return [
            self::KEY => $this->token,
        ];
    }
}
