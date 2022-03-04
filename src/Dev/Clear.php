<?php


namespace SilverStripe\GraphQL\Dev;

use JsonSchema\SchemaStorage;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\DebugView;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaStorageCreator;
use SilverStripe\GraphQL\Schema\Logger;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use SilverStripe\ORM\Connect\NullDatabaseException;
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

        $logger = Logger::singleton();
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
