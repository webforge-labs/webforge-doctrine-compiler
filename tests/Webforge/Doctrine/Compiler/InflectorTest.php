<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Code\Generator\GProperty;
use Webforge\Code\Test\Base;

class InflectorTest extends Base
{
    public function setUp()
    {
        $this->chainClass = __NAMESPACE__ . '\\Inflector';
        parent::setUp();

        $this->inflector = new Inflector();
    }

    /**
     * @dataProvider provideGetterName
     */
    public function testGetterName($expectedName, GProperty $property, $definition)
    {
        $this->assertEquals(
            $expectedName,
            $this->inflector->getPropertyGetterName($property, $definition)
        );
    }

    public static function provideGetterName()
    {
        $tests = array();

        $test = function () use (&$tests) {
            $tests[] = func_get_args();
        };

        $definition = (object)array();

        $test('getId', new GProperty('id'), $definition);
        $test('getOid', new GProperty('oid'), $definition);
        $test('getOids', new GProperty('oids'), $definition);

        return $tests;
    }

    /**
     * @dataProvider provideSetterName
     */
    public function testSetterName($expectedName, GProperty $property, $definition)
    {
        $this->assertEquals(
            $expectedName,
            $this->inflector->getPropertySetterName($property, $definition)
        );
    }

    public static function provideSetterName()
    {
        $tests = array();

        $test = function () use (&$tests) {
            $tests[] = func_get_args();
        };

        $definition = (object)array();

        $test('setId', new GProperty('id'), $definition);
        $test('setOid', new GProperty('oid'), $definition);
        $test('setOids', new GProperty('oids'), $definition);

        return $tests;
    }

    /**
     * @dataProvider provideCollectionAdderName
     */
    public function testCollectionAdderName($expectedName, GProperty $property, $definition)
    {
        $this->assertEquals(
            $expectedName,
            $this->inflector->getCollectionAdderName($property, $definition)
        );
    }

    public static function provideCollectionAdderName()
    {
        $tests = array();

        $test = function () use (&$tests) {
            $tests[] = func_get_args();
        };

        $test('addOid', new GProperty('oids'), (object)array());

        return $tests;
    }

    /**
     * @dataProvider provideTableName
     */
    public function testTableName(stdClass $entity, $expectedTableName)
    {
        $this->assertEquals($expectedTableName, $this->inflector->tableName($entity));
    }

    public static function provideTableName()
    {
        $tests = array();

        $test = function () use (&$tests) {
            $tests[] = func_get_args();
        };

        $test((object)array('name' => 'User'), 'users');
        $test((object)array('name' => 'Author'), 'authors');
        $test((object)array('name' => 'OID', 'tableName' => 'tiptoi_oids'), 'tiptoi_oids');

        return $tests;
    }
}
