<?php

namespace Webforge\Code\Test;

use Webforge\Code\Generator\DocBlock;
use Webforge\Code\Generator\GClass;
use Webforge\Code\Generator\GProperty;
use Webforge\Code\Generator\GFunctionBody;

class GClassTesterTest extends Base
{
    public function setUp()
    {
        $this->chainClass = __NAMESPACE__ . '\\GClassTester';
        parent::setUp();

        $this->gSelf = new GClass(__CLASS__);

        $this->class1 = new GClass('Something');
        $this->class1->createProperty('prop1');
        $this->class1->createProperty('protectedProp', 'String', 'def', GProperty::MODIFIER_PROTECTED);

        $this->class2 = new GClass('SomethingChild');
        $this->class2->setParent($this->class1);
        $this->class2->createMethod('getName', array(), new GFunctionBody(array()));

        $this->class3 = new GClass('Namespaced\One\Something');
    }

    public function testFailingHasNamespace()
    {
        $this->expectFail();
        $this->assertThatGClass($this->class1)->hasNamespace('Other');
    }

    public function testPositiveIsInNamespace()
    {
        $this->assertChainable($this->assertThatGClass($this->class3)->isInNamespace('Namespaced'));
    }

    public function testPositiveIsInNamespace_sameNamespace()
    {
        $this->assertChainable($this->assertThatGClass($this->class3)->isInNamespace('Namespaced\One'));
    }

    public function testNegativeIsInNamespace()
    {
        $this->expectFail();
        $this->assertChainable($this->assertThatGClass($this->class3)->isInNamespace('Namesp'));
    }

    public function testEmptyHasNamespace()
    {
        $this->assertChainable($this->assertThatGClass($this->class1)->hasNamespace(null));
    }

    public function testHasDocBlock()
    {
        $this->gSelf->setDocBlock(new DocBlock('Hello'));

        $this->assertChainable(
            $this->assertThatGClass($this->gSelf)->hasDocBlock()
        );
    }

    public function testPositiveHasParent()
    {
        $this->assertChainable(
            $this->assertThatGClass($this->class2)->hasParent($this->class1)
        );
    }

    public function testNegativeProtectedProperty()
    {
        $tester = $this->assertThatGClass($this->class1)->hasOwnProperty('prop1');

        $this->expectFail();
        $tester->isProtected();
    }

    public function testPositiveProtectedProperty()
    {
        $tester = $this->assertThatGClass($this->class1)->hasOwnProperty('protectedProp');

        $this->expectFail();
        $tester->isProtected();
    }

    public function testPositiveHasParentWithStringAndFQN()
    {
        $this->assertChainable(
            $this->assertThatGClass($this->class2)->hasParent($this->class1->getFQN())
        );
    }

    public function testPositiveHasOwnProperty()
    {
        $this->assertChainable(
            $this->assertThatGClass($this->class1)->hasOwnProperty('prop1')
        );
    }

    public function testFailingHasOwnProperty()
    {
        $this->expectFail();
        $this->assertThatGClass($this->gSelf)->hasOwnProperty('notDefined');
    }

    public function testHasNotOwnProperty()
    {
        $this->assertChainable(
            $this->assertThatGClass($this->gSelf)->hasNotOwnProperty('notDefined')
        );
    }

    public function testPositiveHasMethod()
    {
        $this->assertChainable(
            $this->assertThatGClass($this->class2)->hasMethod('getName')
        );
    }

    public function testGetMethod()
    {
        $this->assertInstanceOf('Webforge\Code\Generator\GMethod', $this->assertThatGClass($this->class2)->getMethod('getName'));
    }

    public function testGetMethodIsPublic()
    {
        $this->assertChainable(
            $this->assertThatGClass($this->class2)->hasMethod('getName')->isPublic()
        );
    }

    public function testNegativeHasMethod()
    {
        $this->expectFail();
        $this->assertThatGClass($this->class2)->hasMethod('undefined');
    }

    public function testNegativeHasNotMethod()
    {
        $this->expectFail();
        $this->assertThatGClass($this->class2)->hasNotMethod('getName');
    }

    public function testPositiveHasNotMethod()
    {
        $this->assertChainable(
            $this->assertThatGClass($this->class2)->hasNotMethod('undefined')
        );
    }


    public function testFailingStatic()
    {
        $this->expectFail();
        $this->assertThatGClass($this->gSelf)->isStatic();
    }

    public function testFailingIsFinal()
    {
        $this->expectFail();
        $this->assertThatGClass($this->gSelf)->isFinal();
    }

    public function testFailingIsAbstract()
    {
        $this->expectFail();
        $this->assertThatGClass($this->gSelf)->isAbstract();
    }

    public function testFailingHasDocBlock()
    {
        $this->expectFail();
        $this->assertThatGClass($this->gSelf)->hasDocBlock();
    }

    protected function expectFail()
    {
        $this->setExpectedException('PHPUnit_Framework_ExpectationFailedException');
    }
}
