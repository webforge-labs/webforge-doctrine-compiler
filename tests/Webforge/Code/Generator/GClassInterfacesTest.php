<?php

namespace Webforge\Code\Generator;

class GClassInterfacesTest extends \Webforge\Code\Test\Base
{
    protected $gClass;

    public function setUp()
    {
        $this->gClass = new GClass(get_class($this));

        $this->exportable = new GClass('Exportable');
        $this->locateable = new GClass('Locateable');
        parent::setUp();
    }

    public function testCanBeAdded()
    {
        $this->gClass->addInterface($this->exportable);

        $this->assertCount(1, $this->gClass->getInterfaces());
        $this->assertEquals(array($this->exportable), $this->gClass->getInterfaces());
    }

    public function testCanBeAddedWithPosition()
    {
        $this->gClass->addInterface($this->exportable);
        $this->gClass->addInterface($this->locateable, 0);

        $this->assertEquals(array($this->locateable, $this->exportable), $this->gClass->getInterfaces());
    }

    public function testHasInterfaceReturns_IfGClassHasInterface()
    {
        $this->assertFalse($this->gClass->hasInterface('a\FQN\I\Made\Up'));

        $this->gClass->addInterface($this->exportable);
        $this->assertTrue($this->gClass->hasInterface('Exportable'));
    }

    public function testCanBeReordered()
    {
        $this->gClass->addInterface($this->exportable);
        $this->gClass->addInterface($this->locateable);

        $this->gClass->setInterfaceOrder($this->locateable, 0);
        $this->assertEquals(array($this->locateable, $this->exportable), $this->gClass->getInterfaces());
    }

    public function testCanBeRemovedByFQN()
    {
        $this->gClass->addInterface($this->exportable);
        $this->gClass->removeInterface('Exportable');

        $this->assertEquals(array(), $this->gClass->getInterfaces());
    }

    public function testCanBeRemovedByClass()
    {
        $this->gClass->addInterface($this->exportable);
        $this->gClass->removeInterface(new GClass('Exportable'));

        $this->assertEquals(array(), $this->gClass->getInterfaces());
    }

    public function testSetInterfacesReplaces()
    {
        $this->gClass->addInterface($this->exportable);

        $this->gClass->setInterfaces(array($this->locateable));
        $this->assertEquals(array($this->locateable), $this->gClass->getInterfaces());
    }
}
