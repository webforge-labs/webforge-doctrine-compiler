<?php

namespace Webforge\Code\Generator;

use Webforge\Types\Type;

class GPropertyTest extends \Webforge\Code\Test\Base
{
    protected $defaultProperty;
    protected $property;

    public function setUp()
    {
        $this->defaultProperty = new GProperty('def');
        $this->property = new GProperty('prop', Type::create('String'));
        $this->publicStaticProperty = new GProperty('pub', Type::create('Integer'), GProperty::MODIFIER_STATIC | GProperty::MODIFIER_PUBLIC);
    }

    public function testDefaultValueIsUndefinedPerDefault()
    {
        $this->assertEquals(GProperty::UNDEFINED, $this->defaultProperty->getDefaultValue());
        $this->assertFalse($this->defaultProperty->hasDefaultValue());
    }

    public function testDefaultTypeIsMixed()
    {
        $this->assertInstanceOf('Webforge\Types\MixedType', $this->defaultProperty->getType());
    }

    public function testItCanBeTestedIfThePropertyTypeIsExplicitOrNot()
    {
        $this->assertTrue($this->property->hasExplicitType());
        $this->assertFalse($this->defaultProperty->hasExplicitType());
    }

    public function testDefaultModifiersIsProtected()
    {
        $this->assertTrue($this->property->isProtected());

        $this->assertFalse($this->property->isStatic());
        $this->assertFalse($this->property->isFinal());
    }

    public function testDefaultValueCanBeSet()
    {
        $this->property->setDefaultValue('defaultString');

        $this->assertTrue($this->property->hasDefaultValue());
        $this->assertEquals('defaultString', $this->property->getDefaultValue());
    }

    public function testDefaultValueCanBeSetToNULL()
    {
        $this->property->setDefaultValue(null);

        $this->assertTrue($this->property->hasDefaultValue());
        $this->assertEquals(null, $this->property->getDefaultValue());

        $this->property->removeDefaultValue();
        $this->assertFalse($this->property->hasDefaultValue());
        $this->assertEquals(GProperty::UNDEFINED, $this->property->getDefaultValue());
    }

    public function testCreateCreatesApropertyWithType()
    {
        $property = GProperty::create('x', Type::create('Integer'));

        $this->assertInstanceOf('Webforge\Code\Generator\GProperty', $property);
        $this->assertInstanceOf('Webforge\Types\IntegerType', $property->getType());
    }

    public function testCreateCreatesApropertyWithTypeAsGClassToobjectType()
    {
        $property = GProperty::create('x', new GClass('PointValue'));

        $this->assertInstanceOf('Webforge\Code\Generator\GProperty', $property);
        $this->assertInstanceOf('Webforge\Types\ObjectType', $property->getType());
        $this->assertEquals('PointValue', $property->getType()->getClassFQN());
    }

    public function testCreateCreatesApropertyArrayType()
    {
        $property = GProperty::create('coordinates', 'Array');
        $this->assertInstanceOf('Webforge\Types\ArrayType', $property->getType());
    }
}
