<?php


namespace SilverStripe\GraphQL\Dev;


use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Dev\DebugView;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\DatabaseAdmin;

class Build extends Controller
{
    private static $url_handlers = [
        '' => 'build'
    ];

    private static $allowed_actions = [
        'build'
    ];

    public function build($request)
    {
        $isBrowser = !Director::is_cli();
        if ($isBrowser) {
            $renderer = DebugView::create();
            echo $renderer->renderHeader();
            echo $renderer->renderInfo("GraphQL Schema Builder", Director::absoluteBaseURL());
            echo "<div class=\"build\">";
        }
        $clear = true; //$request->getVar('clear') ?: false;
        $keys = $request->getVar('schema')
            ? [$request->getVar('schema')]
            : array_keys(Schema::config()->get('schemas'));
        $keys = array_filter($keys, function ($key) {
            return $key !== Schema::ALL;
        });
        foreach ($keys as $key) {
            Benchmark::start('build-schema-' . $key);
            Schema::message(sprintf('--- Building schema "%s" ---', $key));
            $schema = Schema::get($key);
            if ($clear) {
                $schema->getStore()->clear();
            }
            $schema->save();
            Schema::message(
                Benchmark::end('build-schema-' . $key, 'Built schema in %sms.')
            );
        }

        if ($isBrowser) {
            echo "</div>";
            echo $renderer->renderFooter();
        }
    }

}
