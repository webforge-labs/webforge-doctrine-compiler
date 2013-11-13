<?php

namespace Webforge\Doctrine\Compiler;

use ACME\Blog\Entities\Author;
use ACME\Blog\Entities\Post;
use ACME\Blog\Entities\User;

class ModelInheritanceTest extends \Webforge\Doctrine\Compiler\Test\ModelBase {

  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\Compiler';
    parent::setUp();

    $this->authorClass = $this->elevateFull('ACME\Blog\Entities\Author');
    $this->userClass = $this->elevateFull('ACME\Blog\Entities\User');
  }

  public function testEntitiesCanExtendEachOther() {
    // the author is a user
    $authorClass = $this->elevateFull('ACME\Blog\Entities\Author');
    
    $this->assertEquals(
      'Author',
      $authorClass->getName()
    );

    $this->assertThatGClass($authorClass->getParent())
      ->hasParent($this->userClass);

    // @TODO assert that here is a real hierarchy used like: single class table, etc (annotations!)
  }
}
