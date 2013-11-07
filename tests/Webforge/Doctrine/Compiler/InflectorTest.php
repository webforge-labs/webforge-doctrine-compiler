<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Code\Generator\GProperty;

class InflectorTest extends \Webforge\Code\Test\Base {
  
  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\Inflector';
    parent::setUp();

    $this->inflector = new Inflector();
  }

  /**
   * @dataProvider provideGetterName
   */
  public function testGetterName($expectedName, GProperty $property, $definition) {
    $this->assertEquals(
      $expectedName, 
      $this->inflector->getPropertyGetterName($property, $definition)
    );
  }
  
  public static function provideGetterName() {
    $tests = array();
  
    $test = function() use (&$tests) {
      $tests[] = func_get_args();
    };

    $definition = (object) array();
  
    $test('getId', new GProperty('id'), $definition);
  
    return $tests;
  }

  /**
   * @dataProvider provideSetterName
   */
  public function testSetterName($expectedName, GProperty $property, $definition) {
    $this->assertEquals(
      $expectedName, 
      $this->inflector->getPropertySetterName($property, $definition)
    );
  }
  
  public static function provideSetterName() {
    $tests = array();
  
    $test = function() use (&$tests) {
      $tests[] = func_get_args();
    };

    $definition = (object) array();
  
    $test('setId', new GProperty('id'), $definition);
  
    return $tests;
  }
}
