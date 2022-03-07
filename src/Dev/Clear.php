<?php
namespace SilverStripe\GraphQL\Dev;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;

use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use Symfony\Component\Filesystem\Filesystem;

class Clear extends Controller
{
    private static $url_handlers = [
        '' => 'clear',
    ];

    private static $allowed_actions = [
        'clear',
    ];

    public function clear(HTTPRequest $request): void
    {

        $logger = Injector::inst()->get(LoggerInterface::class . '.graphql-build');
        $dirName = CodeGenerationStore::config()->get('dirName');
        $expectedPath = BASE_PATH . DIRECTORY_SEPARATOR . $dirName;
        $fs = new Filesystem();

        $logger->info('Clearing GraphQL code generation directory');
        if ($fs->exists($expectedPath)) {
            $logger->info('Directory has been found');
            $fs->remove($expectedPath);
            $logger->info('Directory has been removed');
        } else {
            $logger->info('Directory was not found. There is nothing to clear');
        }

    }
}
