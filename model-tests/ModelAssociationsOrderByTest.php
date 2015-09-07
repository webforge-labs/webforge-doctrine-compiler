<?php

namespace Webforge\Doctrine\Compiler;

class ModelAssociationsOrderByTest extends \Webforge\Doctrine\Compiler\Test\ModelBase {
  
  public function setUp() {
    parent::setUp();
    $this->postClass = $this->elevateFull('ACME\Blog\Entities\Post');
    $this->authorClass = $this->elevateFull('ACME\Blog\Entities\Author');
  }

  public function testForOrderByAnnotationInManyToMany() {  
    $metadata = $this->assertDoctrineMetadata($this->postClass);

    $categories = $this->assertAssociationMapping('categories', $metadata);

    $this->assertThatObject($categories)
      ->key('orderBy')->isArray()->length(1)
        ->key('position', 'ASC');
  }

  public function testForOrderByAnnotationInOneToMany() {  
    $metadata = $this->assertDoctrineMetadata($this->authorClass);

    $posts = $this->assertAssociationMapping('writtenPosts', $metadata);

    $this->assertThatObject($posts)
      ->key('orderBy')->isArray()->length(1)
        ->key('relevance', 'DESC');
  }
}
