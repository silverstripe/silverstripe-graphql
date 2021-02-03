<?php


namespace SilverStripe\GraphQL\Schema\Services;

use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Path;
use SilverStripe\EventDispatcher\Event\EventHandlerInterface;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Schema;
use Exception;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Persists a graphql schema to a json document consumable by Apollo
 */
class SchemaTranscriber
{
    use Injectable;

    const CACHE_FILENAME = 'types.graphql';

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var GeneratedAssetHandler
     */
    protected $assetHandler;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * SchemaTranscriber constructor.
     * @param Schema $schema
     * @param string $rootDir
     */
    public function __construct(Schema $schema, string $rootDir = PUBLIC_PATH)
    {
        $this->fs = new Filesystem();
        $this->schema = $schema;
        $this->rootDir = $rootDir;
    }

    /**
     * Introspect the schema and persist it to the filesystem
     * @throws Exception
     */
    public function writeSchemaToFilesystem()
    {
        try {
            $types = $this->introspectTypes();
        } catch (Exception $e) {
            throw new Exception(sprintf(
                'There was an error caching the GraphQL types: %s',
                $e->getMessage()
            ));
        }

        $this->writeTypes(json_encode($types));
    }

    /**
     * @return void
     */
    public function removeSchemaFromFilesystem(): void
    {
        $this->fs->remove($this->generateCacheFilename());
    }

    /**
     * @param string $content
     */
    public function writeTypes(string $content)
    {
        $this->fs->dumpFile($this->generateCacheFilename(), $content);
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    /**
     * @param string
     * @return SchemaTranscriber
     */
    public function setRootDir(string $rootDir): self
    {
        $this->rootDir = $rootDir;
        return $this;
    }


    /**
     * @return array
     * @throws SchemaNotFoundException
     * @throws Exception
     */
    private function introspectTypes(): array
    {
        $handler = QueryHandler::create();
        $graphqlSchema = $this->schema->fetch();

        $fragments = $handler->query(
            $graphqlSchema,
            <<<GRAPHQL
query IntrospectionQuery {
    __schema {
      types {
        kind
        name
        possibleTypes {
          name
        }
      }
    }
}
GRAPHQL
        );

        if (isset($fragments['errors'])) {
            $messages = array_map(function ($error) {
                return $error['message'];
            }, $fragments['errors']);

            throw new Exception(sprintf(
                'There were some errors with the introspection query: %s',
                implode(PHP_EOL, $messages)
            ));
        }

        return $fragments;
    }

    /**
     * @return string
     */
    private function generateCacheFilename(): string
    {
        return Path::join(
            $this->rootDir,
            $this->schema->getSchemaKey() . '.' . self::CACHE_FILENAME
        );
    }
}
