<?php

namespace SilverStripe\GraphQL\Schema;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\PolyExecution\PolyOutput;
use Stringable;

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

    private int $level = Logger::INFO;

    private ?PolyOutput $output = null;

    public function setOutput(?PolyOutput $output): void
    {
        $this->output = $output;
    }

    public function setVerbosity(int $level): Logger
    {
        $this->level = $level;

        return $this;
    }

    public function alert(Stringable|string $message, array $context = []):void
    {
        if ($this->level > Logger::ALERT) {
            return;
        }
        $this->output($message, strtoupper(__FUNCTION__), Logger::RED);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        if ($this->level > Logger::CRITICAL) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__), Logger::RED);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        if ($this->level > Logger::DEBUG) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__));
    }

    public function emergency(Stringable|string $message, array $context = []): void
    {
        if ($this->level > Logger::EMERGENCY) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__), Logger::RED);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        if ($this->level > Logger::ERROR) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__), Logger::RED);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        if ($this->level > Logger::INFO) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__), Logger::CYAN);
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->output($message, strtoupper(__FUNCTION__));
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        if ($this->level > Logger::NOTICE) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__), Logger::YELLOW);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        if ($this->level > Logger::WARNING) {
            return;
        }

        $this->output($message, strtoupper(__FUNCTION__), Logger::YELLOW);
    }

    public function output(string $msg, ?string $prefix = null, ?string $colour = null): void
    {
        $prefix = $prefix ? '[' . $prefix . ']: ' : '';
        $this->output?->writeln($this->colouriseText($prefix, $colour) . $msg);
        if (!$this->output) {
            $cli = Director::is_cli();
            $formatted = sprintf(
                '%s%s%s%s',
                $colour && $cli ? $colour :'',
                $prefix,
                $colour && $cli ? Logger::RESET : '',
                $msg
            );
            if ($cli) {
                fwrite(STDOUT, $formatted . PHP_EOL);
            } else {
                echo $formatted . "<br>";
            }
        }
    }

    private function colouriseText(string $msg, ?string $colour): string
    {
        switch ($colour) {
            case Logger::BLACK:
                return "<fg=black>$msg</>";
            case Logger::RED:
                return "<fg=red>$msg</>";
            case Logger::GREEN:
                return "<fg=green>$msg</>";
            case Logger::YELLOW:
                return "<fg=yellow>$msg</>";
            case Logger::BLUE:
                return "<fg=blue>$msg</>";
            case Logger::MAGENTA:
                return "<fg=magenta>$msg</>";
            case Logger::CYAN:
                return "<fg=cyan>$msg</>";
            case Logger::WHITE:
                return "<fg=white;bg=black>$msg</>";
        }
        return $msg;
    }
}
