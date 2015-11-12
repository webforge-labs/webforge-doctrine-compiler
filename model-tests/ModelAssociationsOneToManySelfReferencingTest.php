<?php

namespace Webforge\Doctrine\Compiler;

use ACME\Blog\Entities\Category;
use ACME\Blog\Entities\Tag;
use ACME\Blog\Entities\Author;

class ModelAssociationsOneToManySelfReferencingTest extends \Webforge\Doctrine\Compiler\Test\ModelBase {

  public function setUp() {
    parent::setUp();

    $this->nodeClass = $this->elevateFull('ACME\Blog\Entities\NavigationNode');
  }

  public function testHasAllSettersGettersAddersRemoversAndCheckers() {
    $this->assertThatGClass($this->nodeClass->getParent())
      ->hasProperty('children')
        ->isProtected()
      ->hasMethod('getChildren')
      ->hasMethod('setChildren')
      ->hasMethod('addChild')
      ->hasMethod('removeChild')
      ->hasMethod('hasChild')

      ->hasProperty('parent')
        ->isProtected()
      ->hasMethod('getParent')
      ->hasMethod('setParent')
    ;
  }

  public function testOneToManyDoctrineMetadata_selfReferencing() {
    $metadata = $this->assertDoctrineMetadata($this->nodeClass->getFQN());

    // inverse side
    $children = $this->assertAssociationMapping('children', $metadata, 'OneToMany');

    $this->assertHasTargetEntity($this->nodeClass, $children);
    $this->assertEmpty($children['inversedBy'], 'inversedBy should be empty for inverse side '.print_r($children, true));
    $this->assertIsMappedBy('parent', $children);

    // owning side
    $parent = $this->assertAssociationMapping('parent', $metadata, 'ManyToOne');

    $this->assertHasTargetEntity($this->nodeClass, $parent);
    $this->assertEmpty($parent['mappedBy'], 'mappedBy should be empty for owning side');
    $this->assertIsInversedBy('children', $parent);
  }
}
