<?php

namespace Webforge\Code\Generator;

class GObjectCollectionTest extends \Webforge\Code\Test\Base
{
    protected $c;
    protected $o1, $o2, $o3;

    public function setUp()
    {
        $this->o1 = new TestObject('object 1');
        $this->o2 = new TestObject('object 2');
        $this->o3 = new TestObject('object 3');

        $this->o9 = new TestObject('object 9');

        $this->c = new GObjectCollection(array($this->o1));
    }

    public function testAddAddsToCollection()
    {
        $this->c->add($this->o2);
        $this->c->add($this->o3);

        $this->assertContent(array($this->o1, $this->o2, $this->o3));
    }

    public function testAddAddsToCollectionWithoutParameterAlwaysAtTheEnd()
    {
        $this->c->add($this->o3);
        $this->c->add($this->o2, GObjectCollection::END);

        $this->assertContent(array($this->o1, $this->o3, $this->o2));
    }

    public function testAddAddsToCollectionWithNumericPosition()
    {
        $this->c->add($this->o3);
        $this->c->add($this->o2, 1);

        $this->assertContent(array($this->o1, $this->o2, $this->o3));
    }

    public function testGetFromCollectionByKey()
    {
        $this->assertSame($this->o1, $this->c->get('object 1'));
    }

  /**
   * @expectedException RuntimeException
   */
    public function testGetFromCollectionThrowsExceptionWhenDoesNotExist()
    {
        $this->c->get('object 9');
    }

  /**
   * @expectedException RuntimeException
   */
    public function testGetFromCollectionThrowsExceptionWhenDoesNotExistByIndex()
    {
        $this->c->get(6);
    }

    public function testGetFromCollectionByIndex()
    {
        $this->c->add($this->o2);
        $this->c->add($this->o3);

        $this->assertSame($this->o1, $this->c->get(0));
        $this->assertSame($this->o3, $this->c->get(2));
    }

    public function testHasWithObject()
    {
        $this->assertTrue($this->c->has($this->o1));
        $this->assertFalse($this->c->has($this->o2));
    }

    public function testHasWithKey()
    {
        $this->assertTrue($this->c->has('object 1'));
        $this->assertFalse($this->c->has('object 2'));
    }

    public function testRemoveFromCollection()
    {
        $this->c->remove($this->o1);

        $this->assertContent(array());
    }

    public function testSetOrderWithObject()
    {
        $this->c->add($this->o2);
        $this->c->add($this->o3);

        $this->c->setOrder($this->o3, 0);
        $this->assertContent(array($this->o3, $this->o1, $this->o2));
    }

    public function testSetOrderWithOnlyTwoObjects_Move1ToPosition2()
    {
        $this->c = new GObjectCollection(array($this->o2, $this->o1));

        $this->c->setOrder($this->o1, 0);
        $this->assertContent(array($this->o1, $this->o2));
    }

    public function testSetOrderWithOnlyTwoObjects_Move2ToPositionEnd()
    {
        $this->c = new GObjectCollection(array($this->o2, $this->o1));

        $this->c->setOrder($this->o2, GObjectCollection::END);
        $this->assertContent(array($this->o1, $this->o2));
    }

    public function testGetOrderReturnsTheIndexOfPostionInCollection()
    {
        $this->c->add($this->o2);
        $this->c->add($this->o3);
        $this->c->add($this->o9);

        $this->assertEquals(0, $this->c->getOrder($this->o1));
        $this->assertEquals(1, $this->c->getOrder($this->o2));
        $this->assertEquals(2, $this->c->getOrder('object 3'));

        $this->c->setOrder($this->o9, 0);

        $this->assertEquals(0, $this->c->getOrder('object 9'));
        $this->assertEquals(1, $this->c->getOrder($this->o1));
    }

    protected function assertContent(array $array)
    {
        $collection = $this->c->toArray();
        $this->assertContainsOnlyInstancesOf('Webforge\Code\Generator\TestObject', $collection);

        $this->assertEquals(
            $this->reduceCollection($array, 'key'),
            $this->reduceCollection($collection, 'key')
        );
    }
}

class TestObject extends GObject
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getKey()
    {
        return $this->name;
    }
}
