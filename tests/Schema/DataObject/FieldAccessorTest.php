<?php

namespace SilverStripe\GraphQL\Tests\Schema\DataObject;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Tests\Fake\FakeProduct;
use SilverStripe\GraphQL\Tests\Fake\FakeProductPage;
use SilverStripe\GraphQL\Tests\Fake\FakeReview;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\RelationList;
use SilverStripe\Security\Member;

class FieldAccessorTest extends SapphireTest
{
    /**
     * @var FakeProduct
     */
    private $obj;

    /**
     * @var FieldAccessor
     */
    private $accessor;

    protected static $fixture_file = '../fixtures.yml';

    protected static $extra_dataobjects = [
        FakeProductPage::class,
        FakeProduct::class,
        FakeReview::class,
        Member::class,
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->obj = new FakeProduct();
        $this->accessor = new FieldAccessor();
    }

    public function testNormaliseFieldsNative()
    {
        $result = $this->accessor->normaliseField($this->obj, 'Title');
        $this->assertEquals('Title', $result);

        $result = $this->accessor->normaliseField($this->obj, 'Price');
        $this->assertEquals('Price', $result);

        $result = $this->accessor->normaliseField($this->obj, 'Parent');
        $this->assertEquals('Parent', $result);

        $result = $this->accessor->normaliseField($this->obj, 'ID');
        $this->assertEquals('ID', $result);

        $result = $this->accessor->normaliseField($this->obj, 'LastEdited');
        $this->assertEquals('LastEdited', $result);

        $result = $this->accessor->normaliseField($this->obj, 'Nothing');
        $this->assertNull($result);
    }

    public function testNormaliseFieldCaseInsensitive()
    {
        $result = $this->accessor->normaliseField($this->obj, 'title');
        $this->assertEquals('Title', $result);

        $result = $this->accessor->normaliseField($this->obj, 'prIcE');
        $this->assertEquals('Price', $result);

        $result = $this->accessor->normaliseField($this->obj, 'parent');
        $this->assertEquals('Parent', $result);

        $result = $this->accessor->normaliseField($this->obj, 'id');
        $this->assertEquals('ID', $result);

        $result = $this->accessor->normaliseField($this->obj, 'lAstedIteD');
        $this->assertEquals('LastEdited', $result);

        $result = $this->accessor->normaliseField($this->obj, 'nothING');
        $this->assertNull($result);
    }

    public function testGetters()
    {
        $result = $this->accessor->normaliseField($this->obj, 'reverseTitle');
        $this->assertEquals('reverseTitle', $result);

        $result = $this->accessor->normaliseField($this->obj, 'ReverseTitle');
        $this->assertEquals('ReverseTitle', $result);

        $result = $this->accessor->normaliseField($this->obj, 'reversetitle');
        $this->assertEquals('reversetitle', $result);

        $result = $this->accessor->normaliseField($this->obj, 'link');
        $this->assertEquals('link', $result);

        $result = $this->accessor->normaliseField($this->obj, 'Link');
        $this->assertEquals('Link', $result);
    }

    public function testHasField()
    {
        $this->assertTrue($this->accessor->hasField($this->obj, 'ID'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'LastEdited'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'Title'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'title'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'price'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'PrIcE'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'pArent'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'reviews.author'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'relatedProducts.title'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'parent.anythingGoes'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'link'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'reVerSeTitle'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'id'));

        $this->assertFalse($this->accessor->hasField($this->obj, 'nothing'));
    }

    public function testHasNativeField()
    {
        $this->assertTrue($this->accessor->hasNativeField($this->obj, 'ID'));
        $this->assertTrue($this->accessor->hasNativeField($this->obj, 'LastEdited'));
        $this->assertTrue($this->accessor->hasNativeField($this->obj, 'Title'));
        $this->assertTrue($this->accessor->hasNativeField($this->obj, 'title'));
        $this->assertTrue($this->accessor->hasNativeField($this->obj, 'price'));
        $this->assertTrue($this->accessor->hasNativeField($this->obj, 'PrIcE'));
        $this->assertTrue($this->accessor->hasField($this->obj, 'pArent'));
        $this->assertTrue($this->accessor->hasNativeField($this->obj, 'id'));

        $this->assertFalse($this->accessor->hasNativeField($this->obj, 'reviews.author'));
        $this->assertFalse($this->accessor->hasNativeField($this->obj, 'relatedProducts.title'));
        $this->assertFalse($this->accessor->hasNativeField($this->obj, 'parent.anythingGoes'));
        $this->assertFalse($this->accessor->hasNativeField($this->obj, 'link'));
        $this->assertFalse($this->accessor->hasNativeField($this->obj, 'reVerSeTitle'));
        $this->assertFalse($this->accessor->hasNativeField($this->obj, 'nothing'));
    }

    public function testNativeAccess()
    {
        $this->obj->Title = 'test product';
        $this->obj->Price = 10;
        $this->obj->ID = 5;

        $result = $this->accessor->accessField($this->obj, 'title');
        $this->assertInstanceOf(DBField::class, $result);
        $this->assertEquals('test product', $result->getValue());

        $result = $this->accessor->accessField($this->obj, 'prIce');
        $this->assertInstanceOf(DBField::class, $result);
        $this->assertEquals(10, $result->getValue());

        $result = $this->accessor->accessField($this->obj, 'id');
        $this->assertInstanceOf(DBField::class, $result);
        $this->assertEquals(5, $result->getValue());

        $product1 = $this->objFromFixture(FakeProduct::class, 'product1');

        $result = $this->accessor->accessField($product1, 'Reviews.Rating');
        sort($result);
        $this->assertEquals([1, 2, 3], $result);

        $review2 = $this->objFromFixture(FakeReview::class, 'review2');
        $result = $this->accessor->accessField($review2, 'Author.FirstName');
        $this->assertEquals('Author2', $result);

        $page1 = $this->objFromFixture(FakeProductPage::class, 'productPage1');
        $result = $this->accessor->accessField($page1, 'Products.Reviews.Rating');
        sort($result);
        $this->assertEquals([1, 2, 3], $result);

        $result = $this->accessor->accessField($page1, 'Products.Reviews');
        $this->assertInstanceOf(RelationList::class, $result);
        $this->assertCount(3, $result);

        $result = $this->accessor->accessField($page1, 'Products.Reviews.Max(Rating)');
        $this->assertEquals(3, $result);

        $result = $this->accessor->accessField($page1, 'Products.Reviews.Count()');
        $this->assertEquals(3, $result);
    }
}
