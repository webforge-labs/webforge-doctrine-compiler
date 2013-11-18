<?php

namespace Webforge\Doctrine\Compiler;

use ACME\Blog\Entities\Post;
use ACME\Blog\Entities\Tag;
use ACME\Blog\Entities\Author;

class ModelAssociationsManyToManyUnidirectionalTest extends \Webforge\Doctrine\Compiler\Test\ModelBase {

  public function setUp() {
    parent::setUp();

    // one Post has many Tags
    // the tag does not refer to post

    $this->tagClass = $this->elevateFull('ACME\Blog\Entities\Tag');
    $this->postClass = $this->elevateFull('ACME\Blog\Entities\Post');
  }

  public function testHasAllSettersGettersAddersRemoversAndCheckers() {

    $this->assertThatGClass($this->tagClass->getParent())
      ->hasNotProperty('posts')
      ->hasNotMethod('getPosts')
      ->hasNotMethod('setPosts')
      ->hasNotMethod('addPost')
      ->hasNotMethod('removePost')
      ->hasNotMethod('hasPost')
    ;

    $this->assertThatGClass($this->postClass->getParent())
      ->hasProperty('tags')
        ->isProtected()
      ->hasMethod('setTags', array('tags'))
        ->isPublic()
      ->hasMethod('getTags')
        ->isPublic()
      ->hasMethod('addTag', array('tag'))
      ->hasMethod('removeTag', array('tag'))
      ->hasMethod('hasTag', array('tag'))
    ;
  }

  public function testAddingTheEntityOnTheOwningSide_AddsTheEntityOnTheOtherSide() {
    $hot = new Tag('hot');
    $controversal = new Tag('controversal');
    $post = new Post(new Author());

    $post->addTag($hot);
    $this->assertTrue($post->hasTag($hot));

    $post->addTag($controversal);
    $this->assertTrue($post->hasTag($hot));
    $this->assertTrue($post->hasTag($controversal));
  }

  public function testManyToManyDoctrineMetadata_unidirectional_owningSide() {
    $metadata = $this->assertDoctrineMetadata($this->postClass->getFQN());

    $tags = $this->assertAssociationMapping('tags', $metadata);

    $this->assertHasTargetEntity($this->tagClass, $tags);
    $this->assertEmpty($tags['inversedBy']);

    $this->assertDefaultJoinTable($tags, 'posts2tags', 'tags');
  }

  protected function assertDefaultJoinTable(Array $association, $tableName, $debugName) {
    $this->assertNotEmpty($association['joinTable'], $debugName.' should have a join table');
    $joinTable = (object) $association['joinTable'];

    $this->assertEquals($tableName, $joinTable->name, 'the table name does not match for '.$debugName);

    $this->assertCount(1, $joinTable->joinColumns);
    $this->assertCount(1, $joinTable->inverseJoinColumns);

    $joinColumn = $joinTable->joinColumns[0];
    $inverseJoinColumn = $joinTable->inverseJoinColumns[0];

    list($tab1, $tab2) = explode('2', $tableName);

    $this->assertStringStartsWith($tab1, $joinColumn['name']);
    $this->assertStringStartsWith($tab2, $inverseJoinColumn['name']);

    $this->assertEquals('cascade', $joinColumn['onDelete'], 'the joinColumn should cascade onDelete');
    $this->assertEquals('cascade', $inverseJoinColumn['onDelete'], 'the inverseJoinColumn should cascade onDelete');
  }
}
