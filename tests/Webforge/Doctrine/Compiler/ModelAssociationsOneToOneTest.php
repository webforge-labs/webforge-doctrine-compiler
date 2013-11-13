<?php

namespace Webforge\Doctrine\Compiler;

class ModelAssociationsOneToOneTest extends \Webforge\Doctrine\Compiler\Test\Base {
  
  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\ModelAssociationsOneToOne';
    parent::setUp();
    $this->markTestIncomplete('Its not yet used');
  }

  protected function setUpPackage() {
    $this->blogPackage = self::$package;
  }
}
