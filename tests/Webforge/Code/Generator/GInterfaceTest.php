<?php

namespace Webforge\Code\Generator;

class GInterfaceTest extends \Webforge\Code\Test\Base
{
    protected $gInterface;

    public function setUp()
    {
        $this->gInterface = new GInterface(get_class($this));
    }

    public function testisInterfaceReturnsTrueIfGClassisAlsoInterface()
    {
        $this->assertFalse(GClass::create('whatever')->isInterface());
        $this->assertTrue($this->gInterface->isInterface());
    }
}
