<?php

namespace Webforge\Code\Generator;

/**
 */
class GClassPropertiesTest extends \Webforge\Code\Test\Base
{
    protected $gClass;

    public function setUp()
    {
        $this->xProperty = new GProperty('x');
        $this->yProperty = new GProperty('y');

        $this->gClass = new GClass('Geometric\Point');
        $this->gClass->addProperty($this->yProperty);
    }

    public function testAfterAddingGClassHasAProperty()
    {
        $this->gClass->addProperty($this->xProperty);

        $this->assertTrue($this->gClass->hasProperty($this->xProperty));
        $this->assertTrue($this->gClass->hasProperty('x'));
    }

    public function testAfterCreateAGClassProperty_GClassHasTheProperty()
    {
        $this->gClass->createProperty('x');

        $this->assertTrue($this->gClass->hasProperty('x'));
    }

    public function testAfterCreateAGClassProperty_PropertyHasTheGClass()
    {
        $x = $this->gClass->createProperty('x');

        $this->assertInstanceOf('Webforge\Code\Generator\GProperty', $x);
        $this->assertSame($this->gClass, $x->getGClass());
    }

    public function testCreateCanBeUsedWithGClassAsParameterForTypeOfProperty()
    {
        $this->gClass->createProperty('x', new GClass('PointValue'));

        $this->assertTrue($this->gClass->hasProperty('x'));
        $this->assertInstanceOf('Webforge\Types\ObjectType', $this->gClass->getProperty('x')->getType());
    }

    public function testAfterRemovingAnPropertyTheClassHasThePropertyAnymore()
    {
        $this->gClass->removeProperty('y');
        $this->assertFalse($this->gClass->hasProperty('y'));
        $this->assertFalse($this->gClass->hasProperty($this->yProperty));

        $this->gClass->addProperty($this->xProperty);
        $this->gClass->removeProperty($this->xProperty);
        $this->assertFalse($this->gClass->hasProperty($this->xProperty));
    }

    public function testGetProperties()
    {
        $this->assertCount(1, $this->gClass->getProperties());
        $this->gClass->addProperty($this->xProperty, 0);
        $this->assertCount(2, $this->gClass->getProperties());

        $this->assertContainsOnlyInstancesOf('Webforge\Code\Generator\GProperty', $this->gClass->getProperties());
        $this->assertEquals(array('x','y'), $this->reduceCollection($this->gClass->getProperties(), 'name'));
    }

    public function testSetProperties()
    {
        $this->gClass->setProperties(array($this->xProperty, $this->yProperty));
        $this->assertCount(2, $this->gClass->getProperties());
        $this->assertContainsOnlyInstancesOf('Webforge\Code\Generator\GProperty', $this->gClass->getProperties());
        $this->assertSame($this->gClass, $this->xProperty->getGClass());
        $this->assertSame($this->gClass, $this->yProperty->getGClass());
    }

    public function testOrderCanBeChangedByName()
    {
        $this->gClass->setProperties(array($this->yProperty, $this->xProperty));

        $this->gClass->setPropertyOrder('y', GClass::END);
        $this->assertEquals(array($this->xProperty, $this->yProperty), $this->gClass->getProperties());
    }

    public function testOrderCanBeChangedByProperty()
    {
        $this->gClass->setProperties(array($this->yProperty, $this->xProperty));

        $this->gClass->setPropertyOrder($this->yProperty, GClass::END);
        $this->assertEquals(array($this->xProperty, $this->yProperty), $this->gClass->getProperties());
    }

    public function testGetAllPropertiesForGClassWithParentProperties()
    {
        $parent = new GClass('Geometric\Base');
        $parent->createProperty('info');
        $parent->createProperty('scale');

        $this->gClass->setParent($parent);
        $this->gClass->addProperty($this->xProperty);

        $this->assertGCollectionEquals(array('info','scale','x','y'), $this->gClass->getAllProperties());
        $this->assertGCollectionEquals(array('info','scale','x','y'), $this->gClass->getAllProperties(GClass::WITH_OWN | GClass::WITH_PARENTS));
        $this->assertGCollectionEquals(array('x','y'), $this->gClass->getAllProperties(GClass::WITH_OWN));
        $this->assertGCollectionEquals(array('info','scale'), $this->gClass->getAllProperties(GClass::WITH_PARENTS));
    }

    public function testCreateAPropertyWithModifiersAndDefaultValue()
    {
        $parent = new GClass('Geometric\Base');
        $parent->createProperty('info', $type = null, $default = 'some-value', GProperty::MODIFIER_PUBLIC);

        $property = $parent->getProperty('info');

        $this->assertEquals('info', $property->getName());
        $this->assertEquals(GProperty::MODIFIER_PUBLIC, $property->getModifiers(), 'modifiers are not set correctly');
        $this->assertEquals('some-value', $property->getDefaultValue());
    }

    public function testCreateAConstantWithModifiersAndDefaultValue()
    {
        $parent = new GClass('Geometric\Base');
        $parent->createConstant('info', $type = null, $default = 'some-value', GProperty::MODIFIER_PUBLIC);

        $constant = $parent->getConstant('info');

        $this->assertEquals('info', $constant->getName());
        $this->assertEquals(GConstant::MODIFIER_PUBLIC, $constant->getModifiers(), 'modifiers are not set correctly');
        $this->assertEquals('some-value', $constant->getDefaultValue());
    }
}
