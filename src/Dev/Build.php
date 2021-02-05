<?php


namespace SilverStripe\GraphQL\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\DebugView;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaFactory;
use SilverStripe\ORM\DatabaseAdmin;

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

            $schema = SchemaFactory::singleton()->boot($key);
            if ($clear) {
                $schema->getStore()->clear();
            }
            $schema->process();

            // Allow for "empty" schemas which don't have any types defined.
            // This enables baseline configuration of the "default" schema.
            if ($schema->exists()) {

            } else {
                Schema::message('No types defined, skipping');
                continue;
            }

            $schema->save();

            Schema::message(
                Benchmark::end('build-schema-' . $key, 'Built schema in %sms.')
            );
        }
    }
}
