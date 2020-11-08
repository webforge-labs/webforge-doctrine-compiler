<?php

namespace Webforge\Code\Generator;

class ClassReaderMethodsTest extends ClassReaderBaseTest
{
    public function testReadSimpleGetter()
    {
        $this->inClass(
            'public function getX() {}'
        );

        $this->assertThatGClass($this->read())
        ->hasMethod('getX');
    }

    public function testMethodWithParamsWithClassValue_WithoutDefault()
    {
        $this->inClass(
            'public function setCoordinates(PointValue $x, PointValue $y) {}'
        );

        list($x, $y) = $this->assertThatGClass($gClass = $this->read())
        ->hasMethod('setCoordinates', array('x', 'y'))
        ->get()->getParameters();

        $this->assertInstanceOf('Webforge\Types\ObjectType', $type = $x->getType());
        $this->assertEquals('PointValue', $type->getClassFQN());
        $this->assertEquals(GParameter::UNDEFINED, $x->getDefault());
        $this->assertInstanceOf('Webforge\Types\ObjectType', $type = $y->getType());
        $this->assertEquals('PointValue', $type->getClassFQN());
    }

    public function testArrayParamWithDefaultValueAsArray()
    {
        $this->inClass(
            'public static function createPoint(Array $coords = array(0,0)) {}'
        );

        $coords = $this->assertThatGClass($this->read())
        ->hasMethod('createPoint', array('coords'))
        ->get()->getParameter('coords');

        $this->assertInstanceOf('Webforge\Types\ArrayType', $coords->getType());
        $this->assertEquals(array(0,0), $coords->getDefault());
    }

    public function testDefaultValueAsString()
    {
        $this->inClass(
            'public function setName($name = "unknown") {}'
        );

        $name = $this->assertThatGClass($this->read())
        ->hasMethod('setName', array('name'))
        ->get()->getParameter('name');

        $this->assertEquals('unknown', $name->getDefault());
        $this->assertInstanceOf('Webforge\Types\StringType', $name->getType());
    }

    public function testDefaultValueAsStdClass()
    {
        $this->inClass(
            'public function setInfo(\stdClass $o = NULL) {}'
        );

        $o = $this->assertThatGClass($this->read())
        ->hasMethod('setInfo', array('o'))
        ->get()->getParameter('o');

        $this->assertEquals(null, $o->getDefault());
        $this->assertInstanceOf('Webforge\Types\ObjectType', $o->getType());
    }

    public function testDefaultValueAsClassConstant()
    {
        $this->inClass(
            'public function setFlags($flags = self::UNSIGNED) {}'
        );

        $flags = $this->assertThatGClass($this->read())
        ->hasMethod('setFlags', array('flags'))
        ->get()->getParameter('flags');

        $this->assertInstanceOf('Webforge\Code\Generator\GConstant', $constant = $flags->getDefault());
        $this->assertEquals('UNSIGNED', $constant->getName());
        $this->assertEquals('self', $constant->getGClass()->getFQN());
    }

    public function testDefaultValueAsConstant()
    {
        $this->inClass(
            'public function setFlag($flag = PHP_INT_MAX) {}'
        );

        $flag = $this->assertThatGClass($this->read())
        ->hasMethod('setFlag', array('flag'))
        ->get()->getParameter('flag');

        $this->assertInstanceOf('Webforge\Code\Generator\GConstant', $constant = $flag->getDefault());
        $this->assertEquals('PHP_INT_MAX', $constant->getName());
        $this->assertNull($constant->getGClass());
    }

    public function testDefaultValueAsHex()
    {
        $this->inClass(
            'public function setBitmap($flag = 0x000001) {}'
        );

        $flag = $this->assertThatGClass($this->read())
        ->hasMethod('setBitmap', array('flag'))
        ->get()->getParameter('flag');

        $this->assertEquals(0x000001, $flag->getDefault()); // jaja ich weiss, nur damit ichs nicht vergesse
    }

    public function testStaticMethodModifier()
    {
        $this->inClass(
            'public static function createPoint() {}'
        );

        $gMethod = $this->assertThatGClass($this->read())
                 ->hasMethod('createPoint')->get();

        $this->assertTrue($gMethod->isStatic());
    }

    public function testPublicMethodModifier()
    {
        $this->inClass(
            'public function createPoint() {}'
        );

        $gMethod = $this->assertThatGClass($this->read())
                 ->hasMethod('createPoint')->get();

        $this->assertTrue($gMethod->isPublic());
    }

    public function testProtectedMethodModifier()
    {
        $this->inClass(
            'protected function getName() {}'
        );

        $gMethod = $this->assertThatGClass($this->read())
                 ->hasMethod('getName')->get();

        $this->assertTrue($gMethod->isProtected());
    }

    public function testPrivateMethodModifier()
    {
        $this->inClass(
            'private function getName() {}'
        );

        $gMethod = $this->assertThatGClass($this->read())
                 ->hasMethod('getName')->get();

        $this->assertTrue($gMethod->isPrivate());
    }

    public function testFinalMethodModifier()
    {
        $this->inClass(
            'public final function getName() {}'
        );

        $gMethod = $this->assertThatGClass($this->read())
                 ->hasMethod('getName')->get();

        $this->assertTrue($gMethod->isFinal());
    }

    public function testAbstractMethodModifier()
    {
        $this->inClass(
            'abstract public function createPoint();'
        );

        $gMethod = $this->assertThatGClass($this->read())
                 ->hasMethod('createPoint')->get();

        $this->assertTrue($gMethod->isAbstract());
    }
}
