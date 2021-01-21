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
        $keys = $key ? [$key] : array_keys(Schema::config()->get('schemas'));
        $keys = array_filter($keys, function ($key) {
            return $key !== Schema::ALL;
        });
        foreach ($keys as $key) {
            Benchmark::start('build-schema-' . $key);
            Schema::message(sprintf('--- Building schema "%s" ---', $key));
            $schema = SchemaFactory::singleton()->boot($key);
            if (!$schema->exists()) {
                continue;
            }

            if ($clear) {
                $schema->getStore()->clear();
            }

            $schema->save();
            Schema::message(
                Benchmark::end('build-schema-' . $key, 'Built schema in %sms.')
            );
        }

        BuildState::clear();
    }

    // It's likely that this global state will get removed in the near future, so
    // this is just for BC for the currently more semantically correct BuildState class.

    /**
     * @return Schema|null
     */
    public static function getActiveBuild(): ?Schema
    {
        return BuildState::getActiveBuild();
    }

    /**
     * @return Schema
     * @throws SchemaBuilderException
     */
    public static function requireActiveBuild(): Schema
    {
        return BuildState::requireActiveBuild();
    }
}
