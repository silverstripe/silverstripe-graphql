<?php


namespace SilverStripe\GraphQL\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\DebugView;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
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
     * @param HTTPRequest $request
     * @throws SchemaBuilderException
     */
    public function build(HTTPRequest $request)
    {
        $isBrowser = !Director::is_cli();
        if ($isBrowser) {
            $renderer = DebugView::create();
            echo $renderer->renderHeader();
            echo $renderer->renderInfo("GraphQL Schema Builder", Director::absoluteBaseURL());
            echo "<div class=\"build\">";
        }
        $clear = (bool) $request->getVar('clear');
        $this->buildSchema($request->getVar('schema'), $clear);

        if ($isBrowser) {
            echo "</div>";
            echo $renderer->renderFooter();
        }
    }

    /**
     * @param null $key
     * @param bool $clear
     * @throws SchemaNotFoundException
     * @throws SchemaBuilderException
     */
    public function buildSchema($key = null, $clear = false): void
    {
        Schema::setVerbose(true);
        $keys = $key ? [$key] : array_keys(Schema::config()->get('schemas'));
        $keys = array_filter($keys, function ($key) {
            return $key !== Schema::ALL;
        });
        foreach ($keys as $key) {
            Benchmark::start('build-schema-' . $key);
            Schema::message(sprintf('--- Building schema "%s" ---', $key));
            $builder = SchemaBuilder::singleton();
            try {
                $schema = $builder->boot($key);
                try {
                    $builder->build($schema, $clear);
                } catch (EmptySchemaException $e) {
                    Schema::message('Schema ' . $key . ' is empty. Skipping.');
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
                Schema::message("
                    Your schema configuration requires access to the database. This can happen
                    when you add fields that require type introspection (i.e. custom getters).
                    It is recommended that you specify an explicit type when adding custom getters
                    to your schema."
                );
                if ($candidate) {
                    Schema::message(sprintf("
                    This most likely happened when you tried to add the field '%s' to '%s'",
                        $candidate['args'][1],
                        get_class($candidate['args'][0])
                    ));
                }

                throw $e;
            }

            Schema::message(
                Benchmark::end('build-schema-' . $key, 'Built schema in %sms.')
            );
        }
    }
}
