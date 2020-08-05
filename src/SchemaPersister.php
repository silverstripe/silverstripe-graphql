<?php

namespace SilverStripe\GraphQL;

use Exception;
use SilverStripe\GraphQL\Dev\Benchmark;

/**
 * Class SchemaPersister
 * @package SilverStripe\GraphQL
 */
class SchemaPersister implements SchemaPersisterInterface
{

    /**
     * @var string
     */
    private $fileName = 'schema.php';

    /**
     * @return string
     */
    private function getHash(): string
    {
        return md5('UncleCheese');
    }

    /**
     * @param $data
     * @throws Exception
     */
    public function persistSchema($data)
    {
        Benchmark::start('render');
        $code = $data->renderWith('SilverStripe\\GraphQL\\Schema\\GraphQLTypeRegistry', ['Hash' => $this->getHash()]);
        Benchmark::end('render', 'Code generation took %s ms');
        $code = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $code);
        $php = "<?php\n\n{$code}";
        file_put_contents($this->getFileName(), $php);
    }

    /**
     * @return string
     */
    public function getRegistry()
    {
        require_once($this->getFileName());
        $hash = $this->getHash();
        $namespace = 'SilverStripe\\GraphQL\\Schema\\Generated\\Schema_' . $hash;

        return $namespace . '\\Types';
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return ASSETS_PATH . DIRECTORY_SEPARATOR . 'schema.php';
    }
}
