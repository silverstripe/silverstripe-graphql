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

        $this->buildSchema($request->getVar('schema'));

        if ($isBrowser) {
            echo "</div>";
            echo $renderer->renderFooter();
        }
    }


    public function buildSchema($key = null): void
    {
        $keys = $key ? [$key] : array_keys(Schema::config()->get('schemas'));
        $keys = array_filter($keys, function ($key) {
            return $key !== Schema::ALL;
        });
        foreach ($keys as $key) {
            Benchmark::start('build-schema-' . $key);
            Schema::message(sprintf('--- Building schema "%s" ---', $key));
            $schema = Schema::get($key);

            //if ($clear) { todo: caching isn't great
            $schema->getStore()->clear();
            //}

            $schema->save();
            Schema::message(
                Benchmark::end('build-schema-' . $key, 'Built schema in %sms.')
            );
        }

    }

}
