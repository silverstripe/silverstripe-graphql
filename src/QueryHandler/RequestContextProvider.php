<?php


namespace SilverStripe\GraphQL\QueryHandler;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Interfaces\ContextProvider;

class RequestContextProvider implements ContextProvider
{
    use Injectable;

    const HTTP_METHOD = 'httpMethod';

    private HTTPRequest $request;

    /**
     * HTTPMethodProvider constructor.
     * @param HTTPRequest $request
     */
    public function __construct(HTTPRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @param array $context
     * @return mixed|null
     */
    public static function get(array $context)
    {
        return $context[self::HTTP_METHOD] ?? null;
    }

    /**
     * @return null[]|string[]
     */
    public function provideContext(): array
    {
        $method = null;
        if ($this->request->isGET()) {
            $method = 'GET';
        } elseif ($this->request->isPOST()) {
            $method = 'POST';
        }

        return [
            self::HTTP_METHOD => $method,
        ];
    }
}
