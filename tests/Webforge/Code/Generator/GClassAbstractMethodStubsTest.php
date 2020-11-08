<?php

namespace Webforge\Code\Generator;

use ReflectionClass;

/**
 */
class GClassAbstractMethodStubsTest extends \Webforge\Code\Test\Base
{
    protected $baseType;
    protected $serializable, $exportable;

    public function setUp()
    {
        $this->serializable =
        GInterface::create('Serializable')
        ->createMethod('serialize')
        ->getGClass()
        ;

        $this->baseType =
        GClass::create('baseType')
        ->setAbstract(true)
        ->createMethod('getName')
          ->setAbstract(true)
        ->getGClass()
        ->addInterface($this->serializable)
        ;

        $this->exportable =
        GInterface::create('Exportable')
        ->createMethod('export')
        ->getGClass();
    }

    public function testGetAllMethodsForBaseType()
    {
        $this->assertGCollectionEquals(array('getName', 'serialize'), $this->baseType->getAllMethods());
        $this->assertGCollectionEquals(array('getName'), $this->baseType->getAllMethods(GClass::WITH_OWN));
        $this->assertGCollectionEquals(array('serialize'), $this->baseType->getAllMethods(GClass::WITH_INTERFACE));
        $this->assertGCollectionEquals(
            array('serialize','getName'),
            $this->baseType->getAllMethods(GClass::WITH_INTERFACE | GClass::WITH_OWN)
        );
    }


    public function testGetAllInterfacesForTypeWithParentHasInterface()
    {
        $gClass = new GClass('Child');
        $gClass->setParent($this->baseType);
        $gClass->addInterface($this->exportable);

        $this->assertGCollectionEquals(array('Exportable','Serializable'), $gClass->getAllInterfaces());
        $this->assertGCollectionEquals(array('Exportable','Serializable'), $gClass->getAllInterfaces(GClass::WITH_OWN | GClass::WITH_PARENTS));
        $this->assertGCollectionEquals(array('Exportable'), $gClass->getAllInterfaces(GClass::WITH_OWN));
        $this->assertGCollectionEquals(array('Serializable'), $gClass->getAllInterfaces(GClass::WITH_PARENTS));
    }

    public function testGetAllInterfaceForTypeWithParentHasInterfaceAndParentHasMethods()
    {
        $this->baseType->addInterface($this->serializable);

        $gClass = new GClass('Child');
        $gClass->setParent($this->baseType);
        $gClass->addInterface($this->exportable);
        $gClass->createMethod('getInfo');

        $this->assertGCollectionEquals(
            array('export','serialize','getName'),
            $gClass->getAllMethods(GClass::WITH_INTERFACE | GClass::WITH_PARENTS | GClass::WITH_PARENTS_INTERFACES)
        );

        $this->assertGCollectionEquals(
            array('export','serialize','getName'),
            $gClass->getAllMethods(GClass::FULL_HIERARCHY & ~GClass::WITH_OWN)
        );

        $this->assertGCollectionEquals(
            array('export','serialize','getName','getInfo'),
            $gClass->getAllMethods(),
            'gClass hierarchy has the wrong methods. They should include all from interfaces, parents and self'
        );
    }


    public function testCreateAbstractMethodStubsFindsInterfacesAndAbstractBaseMethods()
    {
        $gClass = new GClass('ConcreteType');
        $gClass->setParent($this->baseType);
        $gClass->addInterface($this->exportable);

        $gClass->createAbstractMethodStubs();

        $this->assertThatGClass($gClass)
        ->hasMethod('getName')
        ->hasMethod('export')
        ->hasMethod('serialize')
        ;
    }
}
