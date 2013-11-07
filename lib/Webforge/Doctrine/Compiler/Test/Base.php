<?php

namespace Webforge\Doctrine\Compiler\Test;

use Webforge\Doctrine\Compiler\Compiler;

class Base extends \Webforge\Code\Test\Base {

  protected $webforge;
  protected $compiler;

  public function setUp() {
    parent::setUp();

    $this->psr0Directory = $this->getVirtualDirectory('psr0Directory');

    $this->webforge = $this->frameworkHelper->getWebforge();
    $this->testPackage = $this->webforge->getPackageRegistry()->addComposerPackageFromDirectory(
      $this->psr0directory
    );

    $this->compiler = new Compiler($this->webforge->getClassWriter());
  }

  protected function getVirtualDirectory($name) {
    $dir = vfsStream::setup($name);

    return new Dir(vfsStream::url($name).'/');
  }
}
