<?php


namespace SilverStripe\GraphQL\Schema\Storage;

use SilverStripe\Core\Injector\Injectable;
use InvalidArgumentException;
use SilverStripe\Core\Path;
use SilverStripe\GraphQL\Schema\Interfaces\Encoder as EncoderInterface;

class Encoder implements EncoderInterface
{
    use Injectable;

    /**
     * @var string
     */
    private $includeFile;

    /**
     * @var object
     */
    private $scope;

    /**
     * @var array
     */
    private $globals = [];

    /**
     * Encoder constructor.
     * @param string $includeFile
     * @param $scope
     */
    public function __construct(string $includeFile, $scope, $globals = [])
    {
        if (!file_exists($includeFile)) {
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
