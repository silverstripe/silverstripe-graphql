<?php

namespace SilverStripe\GraphQL\Dev;

use SilverStripe\Control\Director;

/**
 * @internal
 */
class Benchmark
{
    /**
     * @var array
     */
    private static $benchmarks = [];

    public static function start(string $id): void
    {
        if (isset(self::$benchmarks[$id])) {
            throw new \Exception(sprintf('Benchmark ID %s has already started', $id));
        }
        self::$benchmarks[$id] = microtime(true);
    }

    /**
     * @param string $id
     * @param string|null $message
     * @param bool $return
     * @return string|null
     * @throws \Exception
     */
    public static function end(string $id, string $message = null, bool $return = true): ?string
    {
        $benchmark = self::$benchmarks[$id] ?? null;
        if (!$benchmark) {
            throw new \Exception(sprintf('Benchmark ID %s was never started', $id));
        }
        $diff = microtime(true) - $benchmark;
        $rounded = round($diff ?? 0.0, 3);
        $ms = $rounded * 1000;

        $result = $message ? sprintf($message, $ms) : sprintf('[%s]: %sms', $id, $ms);
        unset(self::$benchmarks[$id]);

        if ($return) {
            return $result;
        }

        echo $result;
        echo Director::is_cli() ? PHP_EOL : "<br>";


        return null;
    }
}
