<?php

namespace Webforge\Doctrine\Compiler\Console;

use Webforge\Code\Test\Base;
use Webforge\Common\System\Container;

class CompileCommandTest extends Base
{
    public function setUp()
    {
        $this->chainClass = __NAMESPACE__ . '\\CompileCommand';
        parent::setUp();
    }

    public function testItCanBeInstantiatedStandalone()
    {
        $compileCommand = new CompileCommand(
            'compile-entities',
            Container::createDefault()->getSystem()
        );
    }
}
