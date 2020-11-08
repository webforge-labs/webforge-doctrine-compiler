<?php

namespace Webforge\Code\Generator;

class GModifiersObjectTest extends \Webforge\Code\Test\Base
{
    protected $o;

    public function setUp()
    {
        $this->chainClass = 'Webforge\Code\Generator\GModifiersObject';
        $this->o = $this->getMockForAbstractClass('GModifiersObject');
        parent::setUp();
    }

  /**
   * @dataProvider provideModifierNames
   */
    public function testModifierSettingAndIs($modifier)
    {
        $set = 'set' . ucfirst($modifier);
        $is = 'is' . ucfirst($modifier);
        $constant = constant($this->chainClass . '::MODIFIER_' . mb_strtoupper($modifier));

      // initial
        $this->assertFalse(($this->o->getModifiers() & $constant) === $constant, $modifier . ' is per default in o. this is not expected');
        $this->assertFalse($this->o->$is(), 'is returns not FALSE when ' . $modifier . ' is not in o->getModifiers()');

      // set to true
        $this->assertChainable($this->o->$set(true));
        $this->assertTrue(($this->o->getModifiers() & $constant) === $constant, $modifier . ' is not in o');
        $this->assertTrue($this->o->$is(), 'is returns not TRUE when ' . $modifier . ' is in o->getModifiers()');

      // so now: "is" is always correct

      // set to false
        $this->assertChainable($this->o->$set(false));
        $this->assertFalse($this->o->$is());
    }

    public function setModifiersCombination()
    {
        $this->o->setModifiers(GModifiersObject::MODIFIER_PROTECTED | GModifiersObject::MODIFIER_STATIC);
        $this->assertTrue($this->o->isProtected(), 'is not protected!');
        $this->assertTrue($this->o->isStatic(), 'is not static!');
    }

    public static function provideModifierNames()
    {
        return array(
        array('abstract'),
        array('static'),
        array('final'),

        array('public'),
        array('protected'),
        array('private')
        );
    }
}
