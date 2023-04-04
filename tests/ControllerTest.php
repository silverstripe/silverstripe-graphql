<?php

namespace SilverStripe\GraphQL\Tests;

use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use ReflectionMethod;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Controller;
use SilverStripe\ORM\DB;

class ControllerTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function providePrepareBacktrace()
    {
        $querySource = <<<'GRAPHQL'
            query ReadFiles($filter: FakeInputType!) {
              readFiles(filter: $filter) {
                ... on FileInterface {
                  id
                }
              }
            }
            GRAPHQL;
        $basicTrace = [
            'file' => '/var/www/public/index.php',
            'line' => 24,
            'function' => 'handle',
            'class' => 'SilverStripe\\Control\\HTTPApplication',
            'type' => '->',
        ];
        return [
            // This is part of a real exception stack trace, reconstructed.
            // Tests handling a parsed graphql query, handling an array, and confirms it handles more than just the one trace item.
            [
                'trace' => [
                    [
                        'file' => '/var/www/vendor/silverstripe/graphql/src/Schema/Storage/AbstractTypeRegistry.php',
                        'line' => 24,
                        'function' => 'fromCache',
                        'class' => 'SilverStripe\\GraphQL\\Schema\\Storage\\AbstractTypeRegistry',
                        'type' => '::',
                        'args' => [
                            'FakeInputType',
                        ],
                    ],
                    [
                        'file' => '/var/www/vendor/webonyx/graphql-php/src/Validator/DocumentValidator.php',
                        'line' => 224,
                        'function' => 'visit',
                        'class' => 'GraphQL\Language\Visitor',
                        'type' => '::',
                        'args' => [
                            Parser::parse(new Source($querySource, 'GraphQL')),
                            ['This is just an array - it was a callable in the original but ultimately it was an array.'],
                        ],
                    ],
                ],
                'expected' => [
                    [
                        'file' => '/var/www/vendor/silverstripe/graphql/src/Schema/Storage/AbstractTypeRegistry.php',
                        'line' => 24,
                        'function' => 'fromCache',
                        'class' => 'SilverStripe\\GraphQL\\Schema\\Storage\\AbstractTypeRegistry',
                        'type' => '::',
                        'args' => [
                            'FakeInputType',
                        ],
                    ],
                    [
                        'file' => '/var/www/vendor/webonyx/graphql-php/src/Validator/DocumentValidator.php',
                        'line' => 224,
                        'function' => 'visit',
                        'class' => 'GraphQL\\Language\\Visitor',
                        'type' => '::',
                        'args' => [
                            '{"kind":"Document","loc":{"start":0,"end":121},"definitions":[{"kind":"OperationDefinition","loc":{"start":0,"end":121},"name":{"kind":"Name","loc":{"start":6,"end":15},"value":"ReadFiles"},"operation":"query","variableDefinitions":[{"kind":"VariableDefinition","loc":{"start":16,"end":39},"variable":{"kind":"Variable","loc":{"start":16,"end":23},"name":{"kind":"Name","loc":{"start":17,"end":23},"value":"filter"}},"type":{"kind":"NonNullType","loc":{"start":25,"end":39},"type":{"kind":"NamedType","loc":{"start":25,"end":38},"name":{"kind":"Name","loc":{"start":25,"end":38},"value":"FakeInputType"}}},"directives":[]}],"directives":[],"selectionSet":{"kind":"SelectionSet","loc":{"start":41,"end":121},"selections":[{"kind":"Field","loc":{"start":45,"end":119},"name":{"kind":"Name","loc":{"start":45,"end":54},"value":"readFiles"},"arguments":[{"kind":"Argument","loc":{"start":55,"end":70},"value":{"kind":"Variable","loc":{"start":63,"end":70},"name":{"kind":"Name","loc":{"start":64,"end":70},"value":"filter"}},"name":{"kind":"Name","loc":{"start":55,"end":61},"value":"filter"}}],"directives":[],"selectionSet":{"kind":"SelectionSet","loc":{"start":72,"end":119},"selections":[{"kind":"InlineFragment","loc":{"start":78,"end":115},"typeCondition":{"kind":"NamedType","loc":{"start":85,"end":98},"name":{"kind":"Name","loc":{"start":85,"end":98},"value":"FileInterface"}},"directives":[],"selectionSet":{"kind":"SelectionSet","loc":{"start":99,"end":115},"selections":[{"kind":"Field","loc":{"start":107,"end":109},"name":{"kind":"Name","loc":{"start":107,"end":109},"value":"id"},"arguments":[],"directives":[]}]}}]}}]}}]}',
                            'Array',
                        ],
                    ],
                ],
            ],
            // Some other parts of a real exception stack trace.
            // Tests handling HTTPRequest (common in stack traces) i.e. validates that objects in arguments just return as the FQCN
            [
                'trace' => [
                    array_merge($basicTrace, [
                        'args' => [
                            new HTTPRequest('GET', '/'),
                        ],
                    ]),
                ],
                'expected' => [
                    array_merge($basicTrace, [
                        'args' => [
                            'SilverStripe\\Control\\HTTPRequest',
                        ],
                    ]),
                ],
            ],
            // Check that the backtrace doesn't require arguments to be included
            [
                'trace' => [$basicTrace],
                'expected' => [$basicTrace],
            ],
            // Validate that the arg character limit is respected
            [
                'trace' => [
                    array_merge($basicTrace, [
                        'args' => [
                            str_repeat('a', 10050),
                        ],
                    ]),
                ],
                'expected' => [
                    array_merge($basicTrace, [
                        'args' => [
                            str_repeat('a', 10000) . '...',
                        ],
                    ]),
                ],
            ],
            // Validate that sensitive arguments get filtered out.
            // Note: There's no way to mock Backtrace::filter_backtrace() so we can't just assert that method gets called.
            [
                'trace' => [
                    array_merge($basicTrace, [
                        'class' => DB::class,
                        'function' => 'connect',
                        'args' => [
                            1,
                            2,
                            3,
                        ],
                    ]),
                ],
                'expected' => [
                    array_merge($basicTrace, [
                        'class' => DB::class,
                        'function' => 'connect',
                        'args' => [
                            '<filtered>',
                            '<filtered>',
                            '<filtered>',
                        ],
                    ]),
                ],
            ],
        ];
    }

    /**
     * @dataProvider providePrepareBacktrace
     */
    public function testPrepareBacktrace(array $trace, array $expected)
    {
        $controller = new Controller(__FUNCTION__);
        $reflectionPrepareBacktrace = new ReflectionMethod($controller, 'prepareBacktrace');
        $reflectionPrepareBacktrace->setAccessible(true);

        $this->assertSame($expected, $reflectionPrepareBacktrace->invoke($controller, $trace));
    }
}
