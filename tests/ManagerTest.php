<?php

namespace SilverStripe\GraphQL\Tests;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Tests\TestTypeCreator;
use SilverStripe\Dev\SapphireTest;

class ManagerTest extends SapphireTest
{

    public function testAddTypeAsNamedString() {
        $manager = new Manager();
        $manager->addType('SilverStripe\GraphQL\Tests\TestTypeCreator', 'mytype');
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\TestTypeCreator',
            $manager->getType('mytype')
        );
    }

    public function testAddTypeAsUnnamedString() {
        $manager = new Manager();
        $manager->addType('SilverStripe\GraphQL\Tests\TestTypeCreator');
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\TestTypeCreator',
            $manager->getType('SilverStripe\GraphQL\Tests\TestTypeCreator')
        );
    }

    public function testAddTypeAsNamedObject() {
        $manager = new Manager();
        $type = new TestTypeCreator();
        $manager->addType($type, 'mytype');
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\TestTypeCreator',
            $manager->getType('mytype')
        );
        $this->assertEquals(
            $type,
            $manager->getType('mytype')
        );
    }

    public function testAddTypeAsUnnamedObject() {
        $manager = new Manager();
        $type = new TestTypeCreator();
        $manager->addType($type);
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\TestTypeCreator',
            $manager->getType('SilverStripe\GraphQL\Tests\TestTypeCreator')
        );
        $this->assertEquals(
            $type,
            $manager->getType('SilverStripe\GraphQL\Tests\TestTypeCreator')
        );
    }
}
