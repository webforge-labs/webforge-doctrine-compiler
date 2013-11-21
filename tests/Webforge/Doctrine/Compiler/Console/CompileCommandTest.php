<?php

namespace Webforge\Doctrine\Compiler\Console;

class CompileCommandTest extends \Webforge\Code\Test\Base {
  
  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\CompileCommand';
    parent::setUp();
  }

  public function testItCanBeInstantiatedStandalone() {
    $compileCommand = new \Webforge\Doctrine\Compiler\Console\CompileCommand(
      'compile-entities', \Webforge\Common\System\Container::createDefault()->getSystem()
    );
  }
}
