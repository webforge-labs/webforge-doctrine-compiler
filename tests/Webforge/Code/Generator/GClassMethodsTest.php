<?php

namespace Webforge\Code\Generator;

class GClassMethodsTest extends \Webforge\Code\Test\Base
{
    protected $gClass;

    public function setUp()
    {
        $this->gClass = new GClass('Point');
        $this->geographicBase = new GClass('GeographicBase');
        $this->geographicBase->setAbstract(true);
        $this->getGeometry = $this->geographicBase->createMethod('getGeometry')->setAbstract(true);
        $this->getScale = $this->geographicBase->createMethod('getScale');

        $this->getX = new GMethod('getX');
        $this->getY = new GMethod('getY');
    }

    public function testMethodsCanBeAdded()
    {
        $xGetter = new GMethod('getX');

        $this->gClass->addMethod($xGetter);
        $this->assertTrue($this->gClass->hasMethod('getX'));
    }

    public function testMethodCreatingIsChainable()
    {
        $this->assertInstanceOf(
            'Webforge\Code\Generator\GClass',
            $gClass = GClass::create('SomeClass')
            ->createMethod('someMethod')
            ->setAbstract(true)
            ->getGClass()
        );

        $this->assertTrue($gClass->hasMethod('someMethod'), 'gClass chained should have someMethod');
    }

    public function testCreateAddsANewMethodtoGClass()
    {
        $getX = $this->gClass->createMethod('getX');

        $this->assertTrue($this->gClass->hasMethod($getX));
    }

    public function testGetMethods()
    {
        $gClass = new GClass('Point');
        $gClass->addMethod($this->getX)->addMethod($this->getY);

        $this->assertEquals(array($this->getX, $this->getY), $gClass->getMethods());
    }

    public function testGetAllMethodsReturnsTheMethodsFromParentAsWell()
    {
        $gClass = new GClass('Point');
        $gClass->setParent($this->geographicBase);
        $gClass->addMethod($this->getX)->addMethod($this->getY);

        $this->assertArrayEquals(
            array($this->getGeometry, $this->getScale, $this->getX, $this->getY),
            $gClass->getAllMethods()
        );
    }

    public function testGetAllMethodsReturnsTheMethodsFromParentAsWell_whatAboutDuplicates()
    {
        $gClass = new GClass('Point');
        $gClass->setParent($this->geographicBase);
        $gClass->addMethod($this->getX)->addMethod($this->getY);
        $gClass->addMethod($this->getScale); // override
      // note: with createMethod the test would fail, because getScale is another method, but you'll get the point

        $this->assertArrayEquals(
            array($this->getGeometry, $this->getScale, $this->getX, $this->getY),
            $gClass->getAllMethods()
        );
    }

    public function testAMethodCanBeRemoved()
    {
        $gClass = new GClass('Point');
        $gClass->addMethod($this->getX);

        $this->assertTrue($gClass->hasMethod('getX'));
        $gClass->removeMethod('getX');

        $this->assertFalse($gClass->hasMethod('getX'));
    }

    public function testTheConstructorCanBeMovedToTop()
    {
        $gClass = new GClass('Point');
        $gClass->addMethod($this->getX);

        $cons = $gClass->createMethod('__construct');
        $this->assertEquals(array($this->getX, $cons), $gClass->getMethods());

        $gClass->setMethodOrder($cons, 0);

        $this->assertEquals(array($cons, $this->getX), $gClass->getMethods());
        $this->assertEquals(0, $gClass->getMethodOrder($cons));
    }

    public function testSetMethods()
    {
        $this->gClass->setMethods(array($this->getX, $this->getY));
        $this->assertCount(2, $this->gClass->getMethods());
        $this->assertContainsOnlyInstancesOf('Webforge\Code\Generator\GMethod', $this->gClass->getMethods());
        $this->assertSame($this->gClass, $this->getX->getGClass());
        $this->assertSame($this->gClass, $this->getY->getGClass());
    }
}
