<?php

namespace Webforge\Code\Generator;

class GClassTest extends \Webforge\Code\Test\Base
{
    protected $gClass;

    public function setUp()
    {
        $this->gClass = new GClass(get_class($this));

        $this->exportable = new GClass('Exportable');
        $this->base = new GClass('GeometricBase');
        $this->serializable = new GInterface('Serializable');
        parent::setUp();
    }

    public function testCreateCanHaveParentAsString()
    {
        $gClass = GClass::create('ACME\Console', 'Webforge\System\Console');
        $this->assertInstanceof('Webforge\Code\Generator\GClass', $gClass);
        $this->assertInstanceof('Webforge\Code\Generator\GClass', $gClass->getParent());
    }

    public function testConstructWithOtherGClassClonesTheClass()
    {
        $gClass = new GClass('someClass');
        $other = new GClass($gClass);

        $this->assertNotSame($gClass, $other);
    }

    public function testExistsReturnsIfClassCanBeAutoloaded()
    {
        $gClass = new GClass('does\not\exist');
        $this->assertFalse($gClass->exists());

        $gClass = new GClass(get_class($this));
        $this->assertTrue($gClass->exists());
    }

    public function testConstructIsRobustToWrongPrefixSlashes()
    {
        $gClass = GClass::create('XML\Object');
        $this->assertEquals('XML', $gClass->getNamespace());
        $this->assertEquals('Object', $gClass->getName());
        $this->assertEquals('XML\Object', $gClass->getFQN());

        $gClass = GClass::create('\XML\Object');
        $this->assertEquals('XML', $gClass->getNamespace());
        $this->assertEquals('Object', $gClass->getName());
        $this->assertEquals('XML\Object', $gClass->getFQN());
    }

    public function testThatParentCanBeSet()
    {
        $gClass = GClass::create('Psc\Code\SpecificGenerator');

        $this->assertInstanceOf(
            'Webforge\Code\Generator\GClass',
            $gClass->setParent($parent = GClass::create('Psc\Code\Generator'))
        );

        $this->assertSame($parent, $gClass->getParent());
    }

    public function testNamespaceCanBeReplacedThroughSet()
    {
        $gClass = GClass::create('XML\Object');
        $gClass->setNamespace('Javascript');
        $this->assertEquals('Javascript', $gClass->getNamespace());

        $this->assertEquals('Javascript\Object', $gClass->getFQN());
    }

    public function testWrongNamespaceGetsNormalized()
    {
        $gClass = GClass::create('XML\Object');
        $gClass->setNamespace('\Wrong\XML');

        $this->assertEquals('Wrong\XML\Object', $gClass->getFQN());
    }

    public function testFQNAndNotFQNClassesNamespaces()
    {
      // test looks little weird, but thats the difference from the psc-cms- GClass
        $noFQN = GClass::create('LParameter');
        $this->assertEquals(null, $noFQN->getNamespace());
        $this->assertEquals('LParameter', $noFQN->getFQN());

        $fqn = GClass::create('\LParameter');
        $this->assertEquals(null, $fqn->getNamespace());
        $this->assertEquals('LParameter', $fqn->getName());
    }

    public function testImportsCanBeAddedAndRemoved()
    {
      // most of this is tested in imports
        $this->gClass->addImport(new GClass('Other\UsedClass'));
        $this->assertTrue($this->gClass->hasImport('UsedClass'));

        $this->gClass->removeImport(new GClass('Other\UsedClass'));
        $this->assertFalse($this->gClass->hasImport('UsedClass'));
    }

    public function testPropertyTypesAsHintsForClassesAreImported()
    {
        $gClass = GClass::create('Point')
        ->createProperty('x', new GClass('PointValue'))
        ->getGClass();

        $this->assertGCollectionEquals(array('PointValue'), $gClass->getImports());
    }

    public function testMethodParameterHintsAreImported()
    {
        $gClass = GClass::create('Point')
        ->createMethod('setX', array(GParameter::create('xValue', new GClass('PointValue'))))
        ->getGClass()
        ;

        $this->assertGCollectionEquals(array('PointValue'), $gClass->getImports());
    }

    public function testGetInterfaceReturnsTheInterfaceFoundByFQNandIndex()
    {
      // allthough this method is a really nonsense..
        $this->exportable->setNamespace('Webforge\Common');

        $gClass = GClass::create('Point')
        ->addInterface($this->exportable);
        ;

        $this->assertSame($this->exportable, $gClass->getInterface('Webforge\Common\Exportable'));
        $this->assertSame($this->exportable, $gClass->getInterface(0));
    }

    public function testInterfacesClassesAreImportedWhenFlagIsset()
    {
        $gClass = GClass::create('Point')
        ->addInterface($this->exportable)
        ;

        $this->assertGCollectionEquals(array('Exportable'), $gClass->getImports(GClass::WITH_INTERFACE));
    }

    public function testInterfacesClassesAreNotImportedPerDefault()
    {
        $gClass = GClass::create('Point')
        ->addInterface($this->exportable)
        ;

        $this->assertGCollectionEquals(array(), $gClass->getImports());
    }

    public function testParentClassIsNotImported()
    {
        $gClass = GClass::create('Point')
        ->setParent(GClass::create('GeometricBase'));

        $this->assertGCollectionEquals(array(), $gClass->getImports());
    }

    public function testNewInstance()
    {
        $gClass = new GClass('Webforge\Common\Exception');
        $exception = $gClass->newInstance(array('just a test error'));

        $this->assertInstanceOf('Webforge\Common\Exception', $exception);
        $this->assertEquals('just a test error', $exception->getMessage());
    }

    public function testGetReflection()
    {
        $this->assertInstanceOf('ReflectionClass', $this->gClass->getReflection());
    }

    public function testNewInstanceWithoutConstructor()
    {
        $gClass = new GClass('MyConstructorThrowsExceptionClass');
        $gClass->setNamespace(__NAMESPACE__);
        $instance = $gClass->newInstance(array(), GClass::WITHOUT_CONSTRUCTOR);

        $this->assertInstanceOf($gClass->getFQN(), $instance);
        $this->assertTrue($instance->checkProperty);
    }

    public function testNewClassInstance()
    {
        $exception = GClass::newClassInstance('Webforge\Common\Exception', array('just a test error'));
        $this->assertInstanceOf('Webforge\Common\Exception', $exception);
        $this->assertEquals('just a test error', $exception->getMessage());

        $exception = GClass::newClassInstance($gClass = new GClass('Webforge\Common\Exception'), array('just a test error'));
        $this->assertInstanceOf('Webforge\Common\Exception', $exception);
        $this->assertEquals('just a test error', $exception->getMessage());

        $exception = GClass::newClassInstance($gClass->getReflection(), array('just a test error'));
        $this->assertInstanceOf('Webforge\Common\Exception', $exception);
        $this->assertEquals('just a test error', $exception->getMessage());
    }

    public function testNewClassInstanceThrowsExceptionIfWrongClassParam()
    {
        $this->setExpectedException('InvalidArgumentException');
        GClass::newClassInstance(function () {
        }, array());
    }

    public function testIfClassExistsGetFileReturnsTheDefiningFile()
    {
        $gClass = new GClass(__CLASS__);

        $this->assertEquals(
            __FILE__,
            (string) $gClass->getFile()
        );
    }

    /**
     * @dataProvider provideIsInNamespace
     */
    public function testIsInNamespace($fqn, $namespace, $result)
    {
        $gClass = new GClass($fqn);
        $this->assertEquals($result, $gClass->isInNamespace($namespace), sprintf("'%s' is in namespace '%s'", $fqn, $namespace));
    }

    public static function provideIsInNamespace()
    {
        $tests = array();

        $test = function () use (&$tests) {
            $tests[] = func_get_args();
        };

        $test('Webforge\Doctrine\Container', 'Webforge', true);
        $test('Webforge\Doctrine\Container', 'Webforge\Doctrine', true);
        $test('Webforge\Doctrine\Container', 'Webforge\Doctrine\Container', false);
        $test('Webforge\Doctrine\Container', null, true);

        $test('Container', null, true);
        $test('Container', 'Webforge', false);

        return $tests;
    }
}

class MyConstructorThrowsExceptionClass
{
    public $checkProperty = true;

    public function __construct()
    {
        throw new \Webforge\Common\Exception('this should not be called');
    }
}
