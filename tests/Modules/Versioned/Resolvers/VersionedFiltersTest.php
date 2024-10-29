<?php

namespace SilverStripe\Versioned\GraphQL\Resolvers;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Resolvers\VersionFilters;
use SilverStripe\GraphQL\Tests\Modules\Versioned\Fake\Fake;
use SilverStripe\Versioned\Mode\Versioned;

// Versioned dependency is optional
// and the following implementation relies on existence of this class (in Versioned)
if (!class_exists(Versioned::class)) {
    return;
}

class VersionedFiltersTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        Fake::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(Versioned::class)) {
            $this->markTestSkipped('Skipped Versioned test ' . __CLASS__);
        }
    }

    public function testItValidatesArchiveDate()
    {
        $filter = new VersionFilters();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ArchiveDate parameter/');
        $filter->validateArgs(['mode' => 'archive']);
    }

    public function testItValidatesArchiveDateFormat()
    {
        $filter = new VersionFilters();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid date/');
        $filter->validateArgs(['mode' => 'archive', 'archiveDate' => '01/12/2018']);
    }

    public function testItValidatesStatusParameter()
    {
        $filter = new VersionFilters();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Status parameter/');
        $filter->validateArgs(['mode' => 'status']);
    }

    public function testItValidatesVersionParameter()
    {
        $filter = new VersionFilters();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Version parameter/');
        $filter->validateArgs(['mode' => 'version']);
    }

    public function testItSetsReadingStateByMode()
    {
        Versioned::withVersionedMode(function () {
            $filter = new VersionFilters();
            $filter->applyToReadingState(['mode' => Versioned::DRAFT]);
            $this->assertEquals(Versioned::DRAFT, Versioned::get_stage());
        });
    }

    public function testItSetsReadingStateByArchiveDate()
    {
        Versioned::withVersionedMode(function () {
            $filter = new VersionFilters();
            $filter->applyToReadingState(['mode' => 'archive', 'archiveDate' => '2018-01-01']);
            $this->assertEquals('2018-01-01', Versioned::current_archived_date());
        });
    }

    public function testItFiltersByStageOnApplyToList()
    {
        $filter = new VersionFilters();
        $record1 = new Fake();
        $record1->Name = 'First version draft';
        $record1->write();

        $record2 = new Fake();
        $record2->Name = 'First version live';
        $record2->write();
        $record2->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        $list = Fake::get();
        $list = $filter->applyToList($list, ['mode' => Versioned::DRAFT]);
        $this->assertCount(2, $list);

        $list = Fake::get();
        $list = $filter->applyToList($list, ['mode' => Versioned::LIVE]);
        $this->assertCount(1, $list);
    }

    public function testItThrowsIfArchiveAndNoDateOnApplyToList()
    {
        $filter = new VersionFilters();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ArchiveDate parameter/');
        $list = Fake::get();
        $filter->applyToList($list, ['mode' => 'archive']);
    }

    public function testItThrowsIfArchiveAndInvalidDateOnApplyToList()
    {
        $filter = new VersionFilters();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid date/');
        $list = Fake::get();
        $filter->applyToList($list, ['mode' => 'archive', 'archiveDate' => 'foo']);
    }


    public function testItThrowsIfVersionAndNoVersionOnApplyToList()
    {
        $filter = new VersionFilters();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Version parameter/');
        $list = Fake::get();
        $filter->applyToList($list, ['mode' => 'version']);
    }

    public function testItSetsArchiveQueryParamsOnApplyToList()
    {
        $filter = new VersionFilters();
        $list = Fake::get();
        $list = $filter->applyToList(
            $list,
            [
                'mode' => 'archive',
                'archiveDate' => '2016-11-08',
            ]
        );

        $this->assertEquals('archive', $list->dataQuery()->getQueryParam('Versioned.mode'));
        $this->assertEquals('2016-11-08', $list->dataQuery()->getQueryParam('Versioned.date'));
    }

    public function testItSetsVersionQueryParamsOnApplyToList()
    {
        $filter = new VersionFilters();
        $list = Fake::get();
        $list = $filter->applyToList(
            $list,
            [
                'mode' => 'version',
                'version' => '5',
            ]
        );

        $this->assertEquals('version', $list->dataQuery()->getQueryParam('Versioned.mode'));
        $this->assertEquals('5', $list->dataQuery()->getQueryParam('Versioned.version'));
    }

    public function testItSetsLatestVersionQueryParamsOnApplyToList()
    {
        $filter = new VersionFilters();
        $list = Fake::get();
        $list = $filter->applyToList(
            $list,
            [
                'mode' => 'latest_versions',
            ]
        );

        $this->assertEquals('latest_versions', $list->dataQuery()->getQueryParam('Versioned.mode'));
    }

    public function testItSetsAllVersionsQueryParamsOnApplyToList()
    {
        $filter = new VersionFilters();
        $list = Fake::get();
        $list = $filter->applyToList(
            $list,
            [
                'mode' => 'all_versions',
            ]
        );

        $this->assertEquals('all_versions', $list->dataQuery()->getQueryParam('Versioned.mode'));
    }

    public function testItThrowsOnNoStatusOnApplyToList()
    {
        $filter = new VersionFilters();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Status parameter/');
        $list = Fake::get();
        $filter->applyToList($list, ['mode' => 'status']);
    }

    public function testStatusOnApplyToList()
    {
        $filter = new VersionFilters();
        $record1 = new Fake();
        $record1->Name = 'Only on draft';
        $record1->write();

        $record2 = new Fake();
        $record2->Name = 'Published';
        $record2->write();
        $record2->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        $record3 = new Fake();
        $record2->Name = 'Will be modified';
        $record3->write();
        $record3->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $record3->Name = 'Modified';
        $record3->write();

        $record4 = new Fake();
        $record4->Name = 'Will be archived';
        $record4->write();
        $record4->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
        $oldID = $record4->ID;
        $record4->delete();
        $list = Fake::get();
        $list = $filter->applyToList(
            $list,
            [
                'mode' => 'status',
                'status' => ['modified']
            ]
        );
        $this->assertListEquals([['ID' => $record3->ID]], $list);

        $list = Fake::get();
        $list = $filter->applyToList(
            $list,
            [
                'mode' => 'status',
                'status' => ['archived']
            ]
        );
        $this->assertCount(1, $list);
        $this->assertEquals($oldID, $list->first()->ID);

        $list = Fake::get();
        $list = $filter->applyToList(
            $list,
            [
                'mode' => 'status',
                'status' => ['draft']
            ]
        );

        $this->assertCount(1, $list);
        $ids = $list->column('ID');
        $this->assertContains($record1->ID, $ids);


        $list = Fake::get();
        $list = $filter->applyToList(
            $list,
            [
                'mode' => 'status',
                'status' => ['draft', 'modified']
            ]
        );

        $this->assertCount(2, $list);
        $ids = $list->column('ID');

        $this->assertContains($record3->ID, $ids);
        $this->assertContains($record1->ID, $ids);

        $list = Fake::get();
        $list = $filter->applyToList(
            $list,
            [
                'mode' => 'status',
                'status' => ['archived', 'modified']
            ]
        );

        $this->assertCount(2, $list);
        $ids = $list->column('ID');
        $this->assertTrue(in_array($record3->ID, $ids ?? []));
        $this->assertTrue(in_array($oldID, $ids ?? []));
    }
}
