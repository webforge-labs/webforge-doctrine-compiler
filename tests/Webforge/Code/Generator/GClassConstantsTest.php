<?php

namespace Webforge\Code\Generator;

class GClassConstantsTest extends \Webforge\Code\Test\Base
{
    protected $gClass;

    public function setUp()
    {
        $this->gClass = new GClass(get_class($this));

        $this->append = new GConstant('APPEND');
        $this->prepend = new GConstant('PREPEND');
        parent::setUp();
    }

    public function testCanBeAdded()
    {
        $this->gClass->addConstant($this->append);

        $this->assertCount(1, $this->gClass->getConstants());
        $this->assertEquals(array($this->append), $this->gClass->getConstants());
    }

    public function testCanBeAddedWithPosition()
    {
        $this->gClass->addConstant($this->append);
        $this->gClass->addConstant($this->prepend, 0);

        $this->assertEquals(array($this->prepend, $this->append), $this->gClass->getConstants());
    }

    public function testHasConstantReturns_IfGClassHasConstant()
    {
        $this->assertFalse($this->gClass->hasConstant('A_CONSTANT_NAME_THAT_IS_BS'));

        $this->gClass->addConstant($this->append);
        $this->assertTrue($this->gClass->hasConstant('APPEND'));
    }

    public function testCanBeReordered()
    {
        $this->gClass->addConstant($this->append);
        $this->gClass->addConstant($this->prepend);

        $this->gClass->setConstantOrder($this->prepend, 0);
        $this->assertEquals(array($this->prepend, $this->append), $this->gClass->getConstants());
    }

    public function testCanBeRemovedByFQN()
    {
        $this->gClass->addConstant($this->append);
        $this->gClass->removeConstant('APPEND');

        $this->assertEquals(array(), $this->gClass->getConstants());
    }

    public function testCanBeRemovedByClass()
    {
        $this->gClass->addConstant($this->append);
        $this->gClass->removeConstant(new GConstant('APPEND'));

        $this->assertEquals(array(), $this->gClass->getConstants());
    }

    public function testConstantCanBeGetByName()
    {
        $this->gClass->addConstant($this->append);
        $this->assertSame($this->append, $this->gClass->getConstant('APPEND'));
    }

    public function testConstantCanBeGetByIndex()
    {
        $this->gClass->addConstant($this->append);
        $this->gClass->addConstant($this->prepend);

        $this->assertSame($this->prepend, $this->gClass->getConstant(1));
    }

    public function testSetConstantsReplacesConstants()
    {
        $this->gClass->addConstant($this->append);

        $this->gClass->setConstants(array($this->prepend));
        $this->assertGCollectionEquals(array('PREPEND'), $this->gClass->getConstants());
    }
}
