<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\System\Dir;
use Doctrine\Common\Cache\ArrayCache;
use Webforge\Code\Generator\GClass;
use Webforge\Common\JS\JSONConverter;

class EntityPropertiesTest extends \Webforge\Doctrine\Compiler\Test\Base {

  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\Compiler';
    parent::setUp();
  }

  protected function setUpPackage() {
    $this->blogPackage = self::$package;
  }

  public function testEntityDoesNotHaveThePropertiesFromTheCompiledEntity() {
    $authorClass = $this->elevateFull('ACME\Blog\Entities\Author');
    
    $this->assertThatGClass($authorClass)
      ->hasNotOwnProperty('writtenPosts')
      ->hasNotOwnProperty('revisionedPosts')
    ;

    $this->assertThatGClass($authorClass->getParent())
      ->hasOwnProperty('writtenPosts')
      ->hasOwnProperty('revisionedPosts')
    ;
  }
}
