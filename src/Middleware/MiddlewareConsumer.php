<?php


namespace SilverStripe\GraphQL\Middleware;

trait MiddlewareConsumer
{

    /**
     * @var Middleware[]
     */
    private $middlewares = [];

    /**
     * @return Middleware[]
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * @param Middleware[] $middlewares
     * @return $this
     */
    public function setMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof Middleware) {
                $this->addMiddleware($middleware);
            }
        }
        return $this;
    }

    /**
     * @param Middleware $middleware
     * @return $this
     */
    public function addMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * @param array $params
     * @param callable $last
     * @return mixed
     */
    protected function executeMiddleware(array $params, callable $last)
    {
        // Reverse middlewares
        $next = $last;
        // Filter out any middlewares that are set to `false`, e.g. via config
        $middlewares = array_reverse(array_filter($this->getMiddlewares() ?? []));
        /** @var Middleware $middleware */
        foreach ($middlewares as $middleware) {
            $next = function ($params) use ($middleware, $next) {
                return $middleware->process($params, $next);
            };
        }

        $result = $next($params);

        return $result;
    }
}
