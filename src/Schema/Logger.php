<?php


namespace SilverStripe\GraphQL\Schema;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injectable;

class Logger implements LoggerInterface
{
    use Injectable;

    const BLACK = "\033[30m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const RESET = "\033[0m";

    const DEBUG = 100;
    const INFO = 200;
    const NOTICE = 250;
    const WARNING = 300;
    const ERROR = 400;
    const CRITICAL = 500;
    const ALERT = 550;
    const EMERGENCY = 600;

    /**
     * @var int
     */
    private $level = self::INFO;

    /**
     * @param int $level
     * @return $this
     */
    public function setVerbosity(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = array())
    {
        if ($this->level > self::ALERT) {
            return;
        }
        $this->output($message, strtoupper(__FUNCTION__), self::RED);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = array())
    {
        if ($this->level > self::CRITICAL) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__), self::RED);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = array())
    {
        if ($this->level > self::DEBUG) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__));
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = array())
    {
        if ($this->level > self::EMERGENCY) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__), self::RED);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = array())
    {
        if ($this->level > self::ERROR) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__), self::RED);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = array())
    {
        if ($this->level > self::INFO) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__), self::CYAN);
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = array())
    {
        $this->output($message, strtoupper(__FUNCTION__));
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = array())
    {
        if ($this->level > self::NOTICE) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__), self::YELLOW);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = array())
    {
        if ($this->level > self::WARNING) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__), self::YELLOW);
    }

    public function output(string $msg, ?string $prefix = null, ?string $colour = null)
    {
        $cli = Director::is_cli();
        $formatted = sprintf(
            '%s%s%s%s',
            $colour && $cli ? $colour :'',
            $prefix ? '[' . $prefix . ']: ' : '',
            $colour && $cli ? self::RESET : '',
            $msg
        );
        if ($cli) {
            fwrite(STDOUT, $formatted . PHP_EOL);
        } else {
            echo $formatted . "<br>";
        }
    }
}
