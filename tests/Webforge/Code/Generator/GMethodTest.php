<?php

namespace Webforge\Code\Generator;

class GMethodTest extends \Webforge\Code\Test\Base
{
    public function setUp()
    {
        $this->x = new GParameter('x');
        $this->setX = new GMethod('setX', array());

        $this->y = new GParameter('y');
        $this->setY = new GMethod('setY', array($this->y));

        $this->y = new GParameter('y');
        $this->setXY = new GMethod('setXY', array($this->x, $this->y));

        $this->z = new GParameter('z');
    }

    public function testBodyMustBeAGFunctionBody()
    {
        $gMethod = new GMethod('setX', array(), new GFunctionBody());
    }

    public function testGetParametersReturnsTheArrayOfParameters()
    {
        $this->assertEquals(array(), $this->setX->getParameters());
        $this->assertEquals(array($this->y), $this->setY->getParameters());
        $this->assertEquals(array($this->x, $this->y), $this->setXY->getParameters());
    }

    public function testParameterCanBeAdded()
    {
        $this->setX->addParameter($this->x);

        $this->assertCount(1, $this->setX->getParameters());
    }

    public function testParameterCanBeRemovedByName()
    {
        $this->setXY->removeParameter('x');
        $this->assertEquals(array($this->y), $this->setXY->getParameters());
    }

    public function testParameterCanBeRemovedByClass()
    {
        $this->setXY->removeParameter($this->y);
        $this->assertEquals(array($this->x), $this->setXY->getParameters());
    }

    public function testParameterCanBeRecievedByIndexOrName()
    {
        $this->assertSame($this->x, $this->setXY->getParameter('x'));
        $this->assertSame($this->x, $this->setXY->getParameterByIndex(0));
        $this->assertSame($this->y, $this->setXY->getParameter(1));
    }

    public function testParameterCanBeRecievedByNameWithSepFunction()
    {
        $this->assertSame($this->x, $this->setXY->getParameterByName('x'));
    }

    public function testReturnsReference_TellsIfReferenceisSet()
    {
        $ref = GMethod::create('returnRef')->setReturnsReference(true);
        $val = GMethod::create('returnValue')->setReturnsReference(false);

        $this->assertTrue($ref->returnsReference());
        $this->assertFalse($val->returnsReference());
    }

    public function testHasReturns_IfMethodHasParameterByName()
    {
        $this->assertFalse($this->setX->hasParameter('x')); // see setup: x is not added to setX
        $this->assertTrue($this->setY->hasParameter('y'));
    }

    public function testHasReturns_IfMethodHasParameterByClass()
    {
        $this->assertFalse($this->setX->hasParameter($this->x));
        $this->assertTrue($this->setY->hasParameter($this->y));
    }

    public function testParameterOrderCanBeChanged()
    {
        $this->setXY->setParameterOrder($this->y, 0);
        $this->assertEquals(array($this->y, $this->x), $this->setXY->getParameters()); // switched
    }

    public function testParameterCanBeAddedWithOrder()
    {
        $xyz = GMethod::create('setXYZ')
        ->addParameter($this->z)
        ->addParameter($this->x, 0)
        ->addParameter($this->y, 1)
        ;

        $this->assertEquals(array($this->x, $this->y, $this->z), $xyz->getParameters());
    }

    public function testIsInInterface()
    {
        $this->assertTrue(
            GInterface::create('interf')
            ->createMethod('someAction')
            ->isInInterface()
        );

        $this->assertFalse(
            GClass::create('ConcreteClass')
            ->createMethod('someAction')
            ->isInInterface()
        );
    }
}
