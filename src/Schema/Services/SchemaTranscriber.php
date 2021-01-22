<?php


namespace SilverStripe\GraphQL\Schema\Services;

use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\EventDispatcher\Event\EventHandlerInterface;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Schema;
use Exception;

/**
 * Persists a graphql schema to a json document consumable by Apollo
 */
class SchemaTranscriber
{
    use Injectable;

    const CACHE_FILENAME = 'types.graphql';

    /**
     * @var array
     */
    private static $dependencies = [
        'assetHandler' => '%$' . GeneratedAssetHandler::class,
    ];

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var GeneratedAssetHandler
     */
    protected $assetHandler;

    /**
     * SchemaTranscriber constructor.
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
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
        if (!$this->getAssetHandler()) {
            return;
        }

        $this->getAssetHandler()->removeContent($this->generateCacheFilename());
    }

    /**
     * @param string $content
     */
    public function writeTypes(string $content)
    {
        if (!$this->getAssetHandler()) {
            return;
        }
        $this->getAssetHandler()->setContent($this->generateCacheFilename(), $content);
    }

    /**
     * @param GeneratedAssetHandler $handler
     * @return $this
     */
    public function setAssetHandler(GeneratedAssetHandler $handler): self
    {
        $this->assetHandler = $handler;

        return $this;
    }

    /**
     * @return GeneratedAssetHandler|null
     */
    public function getAssetHandler(): ?GeneratedAssetHandler
    {
        return $this->assetHandler;
    }

    /**
     * @return array
     * @throws SchemaNotFoundException
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
        return $this->schema->getSchemaKey() . '.' . self::CACHE_FILENAME;
    }
}
