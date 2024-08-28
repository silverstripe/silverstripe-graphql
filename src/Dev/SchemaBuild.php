<?php


namespace SilverStripe\GraphQL\Dev;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Command\DevCommand;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Logger;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use SilverStripe\ORM\Connect\NullDatabaseException;
use SilverStripe\Security\PermissionProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class SchemaBuild extends DevCommand implements PermissionProvider
{
    protected static string $commandName = 'graphql:build';

    protected static string $description = 'Build the GraphQL schema(s)';

    private static array $permissions_for_browser_execution = [
        'CAN_DEV_GRAPHQL',
    ];

    public function getTitle(): string
    {
        return 'GraphQL Schema Builder';
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $originalLogger = Injector::inst()->get(LoggerInterface::class . '.graphql-build');
        try {
            $logger = Logger::singleton();
            $logger->setOutput($output);
            Injector::inst()->registerService($logger, LoggerInterface::class . '.graphql-build');
            $this->buildSchema($input->getOption('schema'), true, $output);
        } finally {
            // Restore default logger back to its starting state
            Injector::inst()->registerService($originalLogger, LoggerInterface::class . '.graphql-build');
        }
        return Command::SUCCESS;
    }

    protected function getHeading(): string
    {
        return '';
    }

    /**
     * @throws SchemaNotFoundException
     * @throws SchemaBuilderException
     */
    public function buildSchema(string $key = null, bool $clear = true): void
    {
        /** @var LoggerInterface $logger */
        $logger = Injector::inst()->get(LoggerInterface::class . '.graphql-build');
        $keys = $key ? [$key] : array_keys(Schema::config()->get('schemas') ?? []);
        $keys = array_filter($keys ?? [], function ($key) {
            return $key !== Schema::ALL;
        });

        // Check for old code dir
        if (is_dir(BASE_PATH . '/.graphql')) {
            $logger->warning(
                'You have a .graphql/ directory in your project root. This is no longer the default
                name. The new directory is named ' . CodeGenerationStore::config()->get('dirName') . '. You may
                want to delete this directory and update your .gitignore file, if you are ignoring the generated
                GraphQL code.'
            );
        }

        foreach ($keys as $key) {
            Benchmark::start('build-schema-' . $key);
            $logger->info(sprintf('--- Building schema "%s" ---', $key));
            $builder = SchemaBuilder::singleton();
            try {
                $schema = $builder->boot($key);
                try {
                    $builder->build($schema, $clear);
                } catch (EmptySchemaException $e) {
                    $logger->warning('Schema ' . $key . ' is empty. Skipping.');
                }
            } catch (NullDatabaseException $e) {
                $candidate = null;
                foreach ($e->getTrace() as $item) {
                    $class = $item['class'] ?? null;
                    $function = $item['function'] ?? null;
                    // This is the only known path to a database query, so we'll offer some help here.
                    if ($class === FieldAccessor::class && $function === 'accessField') {
                        $candidate = $item;
                        break;
                    }
                }
                $logger->warning(
                    "Your schema configuration requires access to the database. This can happen
                    when you add fields that require type introspection (i.e. custom getters).
                    It is recommended that you specify an explicit type when adding custom getters
                    to your schema."
                );
                if ($candidate) {
                    $logger->warning(sprintf(
                        "This most likely happened when you tried to add the field '%s' to '%s'",
                        $candidate['args'][1],
                        get_class($candidate['args'][0])
                    ));
                }

                throw $e;
            }

            $logger->info(Benchmark::end('build-schema-' . $key, 'Built schema in %sms.'));
        }
    }

    public function getOptions(): array
    {
        return [
            new InputOption(
                'schema',
                null,
                InputOption::VALUE_REQUIRED,
                'The name of the schema to be built. If not passed, all schemas will be built',
                suggestedValues: array_keys(Schema::config()->get('schemas') ?? [])
            )
        ];
    }

    public function providePermissions(): array
    {
        return [
            'CAN_DEV_GRAPHQL' => [
                'name' => _t(__CLASS__ . '.CAN_DEV_GRAPHQL_DESCRIPTION', 'Can view and execute /dev/graphql'),
                'help' => _t(__CLASS__ . '.CAN_DEV_GRAPHQL_HELP', 'Can view and execute GraphQL development tools (/dev/graphql).'),
                'category' => DevelopmentAdmin::permissionsCategory(),
                'sort' => 80
            ],
        ];
    }
}
