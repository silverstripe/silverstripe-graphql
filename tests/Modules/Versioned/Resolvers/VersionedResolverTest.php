<?php


namespace SilverStripe\Versioned\GraphQL\Resolvers;

use Exception;
use InvalidArgumentException;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Mode\Versioned;
use SilverStripe\GraphQL\Schema\Schema;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Tests\Modules\Versioned\Fake\Fake;
use SilverStripe\GraphQL\Tests\Modules\Versioned\Fake\FakeResolveInfo;
use SilverStripe\GraphQL\Modules\Versioned\Resolvers\VersionedResolver;
use SilverStripe\GraphQL\Tests\Modules\Versioned\Fake\FakeDataObjectStub;
use SilverStripe\GraphQL\Modules\Versioned\Operations\AbstractPublishOperationCreator;

// Versioned dependency is optional
// and the following implementation relies on existence of this class (in Versioned)
if (!class_exists(Versioned::class)) {
    return;
}

class VersionedResolverTest extends SapphireTest
{
    protected $usesDatabase = true;

    public static $extra_dataobjects = [
        Fake::class,
        FakeDataObjectStub::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(Versioned::class)) {
            $this->markTestSkipped('Skipped Versioned test ' . __CLASS__);
        }
    }

    public function testVersionedRead()
    {
        /* @var Fake|Versioned $record */
        $record = new Fake();
        $record->Name = 'First';
        $record->write();

        $this->logInWithPermission('ADMIN');
        $member = Security::getCurrentUser();

        $list = Fake::get();
        $resolvedList = VersionedResolver::resolveVersionedRead(
            $list,
            ['versioning' => [
                'mode' => Versioned::LIVE
            ]],
            ['currentUser' => $member],
            new FakeResolveInfo()
        );
        $this->assertEquals(
            $resolvedList->Count(),
            0,
            'Excludes draft records records in live mode'
        );

        $record->publishSingle();

        $list = Fake::get();
        $resolvedList = VersionedResolver::resolveVersionedRead(
            $list,
            ['versioning' => [
                'mode' => Versioned::LIVE
            ]],
            ['currentUser' => $member],
            new FakeResolveInfo()
        );
        $this->assertEquals(
            $resolvedList->Count(),
            1,
            'Includes live records records in live mode'
        );
        $this->assertEquals(
            $resolvedList->First()->ID,
            $record->ID,
            'Includes live records records in live mode'
        );
    }

    public function testCopyToStage()
    {
        /* @var Fake|Versioned $record */
        $record = new Fake();
        $record->Name = 'First';
        $record->write(); // v1

        $this->logInWithPermission('ADMIN');
        $member = Security::getCurrentUser();
        $resolve = VersionedResolver::resolveCopyToStage(['dataClass' => Fake::class]);
        $resolve(
            null,
            [
                'input' => [
                    'fromStage' => Versioned::DRAFT,
                    'toStage' => Versioned::LIVE,
                    'id' => $record->ID,
                ],
            ],
            [ 'currentUser' => $member ],
            new FakeResolveInfo()
        );
        $recordLive = Versioned::get_by_stage(Fake::class, Versioned::LIVE)
            ->byID($record->ID);
        $this->assertNotNull($recordLive);
        $this->assertEquals($record->ID, $recordLive->ID);

        $record->Name = 'Second';
        $record->write();
        $newVersion = $record->Version;

        $recordLive = Versioned::get_by_stage(Fake::class, Versioned::LIVE)
            ->byID($record->ID);
        $this->assertEquals('First', $recordLive->Title);

        // Invoke publish
        $resolve(
            null,
            [
                'input' => [
                    'fromVersion' => $newVersion,
                    'toStage' => Versioned::LIVE,
                    'id' => $record->ID,
                ],
            ],
            [ 'currentUser' => $member ],
            new FakeResolveInfo()
        );
        $recordLive = Versioned::get_by_stage(Fake::class, Versioned::LIVE)
            ->byID($record->ID);
        $this->assertEquals('Second', $recordLive->Title);

        // Test error
        $this->expectException(\InvalidArgumentException::class);
        $resolve(
            null,
            [
                'input' => [
                    'toStage' => Versioned::DRAFT,
                    'id' => $record->ID,
                ],
            ],
            [ 'currentUser' => new Member() ],
            new FakeResolveInfo()
        );
    }

    public function testPublish()
    {
        $record = new Fake();
        $record->Name = 'First';
        $record->write();

        $result = Versioned::get_by_stage(Fake::class, Versioned::LIVE)
            ->byID($record->ID);

        $this->assertNull($result);
        $this->logInWithPermission('ADMIN');
        $member = Security::getCurrentUser();
        $resolve = VersionedResolver::resolvePublishOperation([
            'dataClass' => Fake::class,
            'action' => AbstractPublishOperationCreator::ACTION_PUBLISH
        ]);
        $resolve(
            null,
            [
                'id' => $record->ID
            ],
            [ 'currentUser' => $member ],
            new FakeResolveInfo()
        );
        $result = Versioned::get_by_stage(Fake::class, Versioned::LIVE)
            ->byID($record->ID);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Fake::class, $result);
        $this->assertEquals('First', $result->Name);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/^Not allowed/');
        $resolve(
            null,
            [
                'id' => $record->ID
            ],
            [ 'currentUser' => new Member() ],
            new FakeResolveInfo()
        );
    }

    public function testUnpublish()
    {
        $record = new Fake();
        $record->Name = 'First';
        $record->write();
        $record->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $result = Versioned::get_by_stage(Fake::class, Versioned::LIVE)
            ->byID($record->ID);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Fake::class, $result);
        $this->assertEquals('First', $result->Name);

        $this->logInWithPermission('ADMIN');
        $member = Security::getCurrentUser();
        $doResolve = VersionedResolver::resolvePublishOperation([
            'dataClass' => Fake::class,
            'action' => AbstractPublishOperationCreator::ACTION_UNPUBLISH
        ]);
        $doResolve(
            null,
            [
                'id' => $record->ID
            ],
            [ 'currentUser' => $member ],
            new FakeResolveInfo()
        );
        $result = Versioned::get_by_stage(Fake::class, Versioned::LIVE)
            ->byID($record->ID);

        $this->assertNull($result);

        $record->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/^Not allowed/');
        $doResolve(
            null,
            [
                'id' => $record->ID
            ],
            [ 'currentUser' => new Member() ],
            new FakeResolveInfo()
        );
    }

    public function testRollbackCannotBePerformedWithoutEditPermission()
    {
        // Create a fake version of our stub
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Current user does not have permission to roll back this resource/');

        $stub = FakeDataObjectStub::create();
        $stub->Name = 'First';
        $stub->Editable = false;
        $stub->write();

        $this->doRollbackMutation($stub);
    }

    public function testRollbackRecursiveIsCalled()
    {
        // Create a fake version of our stub
        $stub = FakeDataObjectStub::create();
        $stub->Name = 'First';
        $stub->write();

        $this->doRollbackMutation($stub);

        $this->assertTrue($stub::$rollbackCalled, 'RollbackRecursive was called');
    }

    protected function doRollbackMutation(DataObject $stub, $toVersion = 1, $member = null)
    {
        if (!$stub->isInDB()) {
            $stub->write();
        }

        $doRollback = VersionedResolver::resolveRollback(['dataClass' => get_class($stub)]);
        $args = [
            'id' => $stub->ID,
            'toVersion' => $toVersion,
        ];

        $doRollback(
            null,
            $args,
            [ 'currentUser' => $member ?: Security::getCurrentUser() ],
            new FakeResolveInfo()
        );
    }
}
