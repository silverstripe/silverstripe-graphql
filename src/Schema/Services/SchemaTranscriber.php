<?php


namespace SilverStripe\GraphQL\Schema\Services;

use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Path;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use GraphQL\Type\Schema as GraphQLSchema;
use Exception;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Persists a graphql schema to a json document consumable by Apollo
 */
class SchemaTranscriber
{
    use Injectable;

    const CACHE_FILENAME = 'types.graphql';

    private GraphQLSchema $schema;

    private string $name;

    protected GeneratedAssetHandler $assetHandler;

    private Filesystem $fs;

    private string $rootDir;

    /**
     * SchemaTranscriber constructor.
     *
     * @param GraphQLSchema $schema
     * @param string $name
     * @param string $rootDir Storage path for the generated file.
     *                        Caution: This location may be used by frontend assets relying on GraphQL, e.g. silverstripe/assets.
     */
    public function __construct(GraphQLSchema $schema, string $name, string $rootDir = null)
    {
        $this->fs = new Filesystem();
        $this->schema = $schema;
        $this->name = $name;
        $this->rootDir = $rootDir ?: Path::join(PUBLIC_PATH, '_graphql');
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

    public function removeSchemaFromFilesystem(): void
    {
        $this->fs->remove($this->generateCacheFilename());
    }

    public function writeTypes(string $content)
    {
        $this->fs->dumpFile($this->generateCacheFilename(), $content);
    }

    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    public function setRootDir(string $rootDir): self
    {
        $this->rootDir = $rootDir;
        return $this;
    }

    /**
     * @throws Exception
     */
    private function introspectTypes(): array
    {
        $handler = QueryHandler::create();
        $fragments = $handler->query(
            $this->schema,
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
            }, $fragments['errors'] ?? []);

            throw new Exception(sprintf(
                'There were some errors with the introspection query: %s',
                implode(PHP_EOL, $messages)
            ));
        }

        return $fragments;
    }

    private function generateCacheFilename(): string
    {
        return Path::join(
            $this->rootDir,
            $this->name . '.' . self::CACHE_FILENAME
        );
    }
}
