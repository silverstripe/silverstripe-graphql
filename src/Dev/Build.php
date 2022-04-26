<?php


namespace SilverStripe\GraphQL\Dev;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\DebugView;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Logger;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use SilverStripe\ORM\Connect\NullDatabaseException;

class Build extends Controller
{
    private static $url_handlers = [
        '' => 'build'
    ];

    private static $allowed_actions = [
        'build'
    ];

    /**
     * @throws SchemaBuilderException
     * @throws SchemaNotFoundException
     */
    public function build(HTTPRequest $request): void
    {
        $isBrowser = !Director::is_cli();
        if ($isBrowser) {
            $renderer = DebugView::create();
            echo $renderer->renderHeader();
            echo $renderer->renderInfo("GraphQL Schema Builder", Director::absoluteBaseURL());
            echo "<div class=\"build\">";
        }
        $clear = true;

        $this->buildSchema($request->getVar('schema'), $clear);

        if ($isBrowser) {
            echo "</div>";
            echo $renderer->renderFooter();
        }
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
                $logger->warning("
                    Your schema configuration requires access to the database. This can happen
                    when you add fields that require type introspection (i.e. custom getters).
                    It is recommended that you specify an explicit type when adding custom getters
                    to your schema.");
                if ($candidate) {
                    $logger->warning(sprintf(
                        "
                    This most likely happened when you tried to add the field '%s' to '%s'",
                        $candidate['args'][1],
                        get_class($candidate['args'][0])
                    ));
                }

                throw $e;
            }

            $logger->info(
                Benchmark::end('build-schema-' . $key, 'Built schema in %sms.')
            );
        }
    }
}
