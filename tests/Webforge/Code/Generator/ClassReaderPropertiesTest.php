<?php

namespace Webforge\Code\Generator;

class ClassReaderPropertiesTest extends ClassReaderBaseTest
{
    public function testPublicStatic()
    {
        $this->inClass(
            'public static $info;'
        );

        $property = $this->assertThatGClass($this->read())
        ->hasProperty('info')
        ->isPublic()
        ->get();
    }

    public function testProtected()
    {
        $this->inClass(
            'protected $x;'
        );

        $property = $this->assertThatGClass($this->read())
        ->hasProperty('x')
        ->isProtected()
        ->get();
    }

    public function testProtectedWithArrayValue()
    {
        $this->inClass(
            <<<'PHP'
      protected $cpx = array(0=>'zero', 'duo', array('nested'));
    PHP
        );

        $property = $this->assertThatGClass($this->read())
        ->hasProperty('cpx')
        ->isProtected()
        ->get();

        $this->assertInstanceOf('Webforge\Types\ArrayType', $property->getType());
        $this->assertEquals(array(0 => 'zero', 1 => 'duo', array('nested')), $property->getDefaultValue());
    }

    public function testPrivate()
    {
        $this->inClass(
            'private $x;'
        );

        $property = $this->assertThatGClass($this->read())
        ->hasProperty('x')
        ->isPrivate()
        ->get();
    }

    public function testAConstant()
    {
        $this->inClass(
            'const FLAG_NUM1 = 0x000001;'
        );

        $property = $this->assertThatGClass($this->read())
        ->hasConstant('FLAG_NUM1')
        ->isPublic()
        ->get();
    }

    public function testTypeFromDocBlock()
    {
        $this->inClass(
            <<<'PHP'
  /**
   * @var Integer
   */
  protected $x;
    PHP
        );

        $property = $this->assertThatGClass($this->read())
        ->hasProperty('x')
        ->isType('Webforge\Types\IntegerType')
        ->get();
    }
}
