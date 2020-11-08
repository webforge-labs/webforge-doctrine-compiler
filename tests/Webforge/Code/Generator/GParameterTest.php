<?php

namespace Webforge\Code\Generator;

use Webforge\Types\Type;

class GParameterTest extends \Webforge\Code\Test\Base
{
    public function setUp()
    {
        $this->classParam = GParameter::create('coordinates', $this->classHint = new GClass('Webforge\Data\Models\Coordinates'));
        $this->arrayParam = GParameter::create('simpleCoords', 'Array');
        $this->unknownParam = new GParameter('unknown');
        $this->stringParam = $this->defParam = new GParameter('enume', $this->getType('String'), 'def');
    }

    public function testHintCanBeStringArrayForCreate()
    {
        $arrayParam = GParameter::create('coordinates', 'array');
        $this->assertInstanceOf('Webforge\Types\ArrayType', $arrayParam->getType());
        $this->assertEquals('array', mb_strtolower($arrayParam->getHint()));
    }

    public function testGparameterDefaultTypeIsMixed()
    {
        $this->assertEquals('Mixed', $this->unknownParam->getType()->getName());
    }

    public function testHasHintReturnsIfParameterHasaHint()
    {
        $this->assertTrue($this->classParam->hasHint());
        $this->assertTrue($this->arrayParam->hasHint());

        $this->assertFalse($this->unknownParam->hasHint());
        $this->assertFalse($this->stringParam->hasHint());
    }

    public function testGetHintImportReturnsGClassIfTypeIsClass()
    {
        $this->assertInstanceOf('Webforge\Code\Generator\GClass', $this->classParam->getHintImport());
        $this->assertEquals($this->classHint->getFQN(), $this->classParam->getHintImport()->getFQN());

        $this->assertNull($this->arrayParam->getHintImport());
        $this->assertNull($this->unknownParam->getHintImport());
        $this->assertNull($this->stringParam->getHintImport());
    }

    public function testReturnsStringIfTypeIsNotClass()
    {
        $this->assertEquals('Array', $this->arrayParam->getHint());
    }

    public function testReturnsNullfTypeCannotBeHinted()
    {
        $this->assertNull($this->unknownParam->getHint());
        $this->assertNull($this->stringParam->getHint());
    }

    public function testisArrayIsTrueForCreatedWithArrayHintProperty()
    {
        $this->assertTrue(GParameter::create('coordinates', 'array')->isArray());
    }

    public function testParamIsOptionalIfItHasADefaultValue()
    {
        $this->assertTrue($this->defParam->isOptional());
    }

    public function testHasDefaultValueIsTrue_IfItHasADefaultValue()
    {
        $param = new GParameter('meanDefault', $this->getType('String'), null);
        $this->assertTrue($param->hasDefault());
        $this->assertTrue($param->isOptional());

        $param->setDefault(GParameter::UNDEFINED);
        $this->assertFalse($param->hasDefault(), 'hasDefault is not false after setting to undefined ');
        $this->assertFalse($param->isOptional(), 'isOptional is not false after setting to undefined');
    }

    public function testDefaultValueCanBeRemoved()
    {
        $param = new GParameter('meanDefault', $this->getType('String'), null);
        $param->removeDefault();

        $this->assertFalse($param->hasDefault());
    }

    public function testParametersNameCanBeChanged()
    {
        $param = new GParameter('paramA');
        $this->assertEquals('paramA', $param->getName());
        $param->setName('paramB');
        $this->assertEquals('paramB', $param->getName());
    }

    public function testItCanBeTestedIfTheTypeIsExplicitOrNot()
    {
        $this->assertTrue($this->classParam->hasExplicitType());
        $this->assertFalse($this->unknownParam->hasExplicitType());
    }

    public function testCreateCreatesApropertyWithType()
    {
        $param = GParameter::create('xValue', $this->getType('Integer'));

        $this->assertInstanceOf('Webforge\Code\Generator\GParameter', $param);
        $this->assertInstanceOf('Webforge\Types\IntegerType', $param->getType());
    }

    public function testCreateCreatesApropertyWithTypeAsGClassToobjectType()
    {
        $param = GParameter::create('xValue', new GClass('PointValue'));

        $this->assertInstanceOf('Webforge\Code\Generator\GParameter', $param);
        $this->assertInstanceOf('Webforge\Types\ObjectType', $param->getType());
        $this->assertEquals('PointValue', $param->getType()->getClassFQN());
    }

    public function getType($type)
    {
        return Type::create($type);
    }
}
