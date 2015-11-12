<?php

namespace Webforge\Doctrine\Compiler;

use ACME\Blog\Entities\Category;
use ACME\Blog\Entities\Tag;
use ACME\Blog\Entities\Author;

class ModelAssociationsManyToManySelfReferencingTest extends \Webforge\Doctrine\Compiler\Test\ModelBase {

  public function setUp() {
    parent::setUp();

    $this->categoryClass = $this->elevateFull('ACME\Blog\Entities\Category');
  }

  public function testHasAllSettersGettersAddersRemoversAndCheckers() {
    $this->assertThatGClass($this->categoryClass->getParent())
      ->hasProperty('relatedCategories')
        ->isProtected()
      ->hasProperty('relatedCategories')
      ->hasMethod('getRelatedCategories')
      ->hasMethod('setRelatedCategories')
      ->hasMethod('addRelatedCategory')
      ->hasMethod('removeRelatedCategory')
      ->hasMethod('hasRelatedCategory')
    ;
  }

  public function testManyToManyDoctrineMetadata_selfReferencing_unidirectional() {
    $metadata = $this->assertDoctrineMetadata($this->categoryClass->getFQN());

    $relatedCategories = $this->assertAssociationMapping('relatedCategories', $metadata);

    $this->assertHasTargetEntity($this->categoryClass, $relatedCategories);
    $this->assertEmpty($relatedCategories['inversedBy']);

    $this->assertJoinTable($relatedCategories, 'categories2categories', 'relatedCategories');
  }

  public function testManyToManyDoctrineMetadata_selfReferencing_unidirectional_withJoinTableName() {
    $metadata = $this->assertDoctrineMetadata($this->categoryClass->getFQN());

    $parentCategories = $this->assertAssociationMapping('parentCategories', $metadata);

    $this->assertHasTargetEntity($this->categoryClass, $parentCategories);
    $this->assertEmpty($parentCategories['inversedBy'], 'inversedBy should not be set');

    $this->assertJoinTable($parentCategories, 'parent_categories', 'parentCategories');
  }
}
