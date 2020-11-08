<?php

namespace Webforge\Code;

class ExceptionsConstructorsTest extends \Webforge\Code\Test\Base
{
    public function testClassNotFoundException_fromFQN()
    {
        $e = ClassNotFoundException::fromFQN('this\class\is\missing');
        $this->assertInstanceOf(\Webforge\Code\ClassNotFoundException::class, $e);
        $this->assertContains('this\class\is\missing', $e->getMessage());
    }
}
