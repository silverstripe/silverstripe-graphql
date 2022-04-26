<?php


namespace SilverStripe\GraphQL\Schema\Storage;

use SilverStripe\Core\Injector\Injectable;
use InvalidArgumentException;
use SilverStripe\Core\Path;
use SilverStripe\GraphQL\Schema\Interfaces\Encoder as EncoderInterface;

class Encoder implements EncoderInterface
{
    use Injectable;

    private string $includeFile;

    /**
     * @var object|array
     */
    private $scope;

    private array $globals = [];

    /**
     * @param object|array $scope
     */
    public function __construct(string $includeFile, $scope, array $globals = [])
    {
        if (!file_exists($includeFile ?? '')) {
            throw new InvalidArgumentException(sprintf(
                'File "%s" does not exist',
                $includeFile
            ));
        }
        $this->includeFile = $includeFile;
        $this->scope = $scope;
        $this->globals = $globals;
    }

    public function encode(): string
    {
        ob_start();
        $scope = $this->scope;
        $globals = $this->globals;
        include($this->includeFile);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
