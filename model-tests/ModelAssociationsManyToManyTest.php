<?php

namespace Webforge\Doctrine\Compiler;

use ACME\Blog\Entities\Category;
use ACME\Blog\Entities\Post;
use ACME\Blog\Entities\Author;

class ModelAssociationsManyToManyTest extends \Webforge\Doctrine\Compiler\Test\ModelBase {

  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\Compiler';
    parent::setUp();

    // one Category has many Posts
    // one Post has many Categories

    $this->categoryClass = $this->elevateFull('ACME\Blog\Entities\Category');
    $this->postClass = $this->elevateFull('ACME\Blog\Entities\Post');
  }

  public function testHasAllSettersGettersAddersRemoversAndCheckers() {

    $this->assertThatGClass($this->categoryClass->getParent())

      ->hasProperty('posts')
        ->isProtected()
      ->hasMethod('getPosts')
        ->isPublic()
      ->hasMethod('setPosts', array('posts'))
        ->isPublic()
      ->hasMethod('addPost', array('post'))
      ->hasMethod('removePost', array('post'))
      ->hasMethod('hasPost', array('post'))
    ;

    $this->assertThatGClass($this->postClass->getParent())
      ->hasProperty('categories')
        ->isProtected()
      ->hasMethod('setCategories', array('categories'))
        ->isPublic()
      ->hasMethod('getCategories')
        ->isPublic()
      ->hasMethod('addCategory', array('category'))
      ->hasMethod('removeCategory', array('category'))
      ->hasMethod('hasCategory', array('category'))
    ;
  }

  public function testAddingTheEntityOnTheOwningSide_AddsTheEntityOnTheOtherSide() {
    $science = new Category('Science');
    $politics = new Category('Politics');
    $post = new Post(new Author('p.scheit@ps-webforge.com'));

    $post->addCategory($science);
    $this->assertTrue($post->hasCategory($science));

    $this->assertTrue($science->hasPost($post));

    $post->addCategory($politics);
    $this->assertTrue($post->hasCategory($politics));
    $this->assertTrue($science->hasPost($post));
    $this->assertTrue($politics->hasPost($post));
  }

  public function testManyToManyDoctrineMetadata_bidirectional_owningSide() {
    $metadata = $this->assertDoctrineMetadata($this->postClass->getFQN());

    $categories = $this->assertAssociationMapping('categories', $metadata);

    $this->assertHasTargetEntity($this->categoryClass, $categories);
    $this->assertIsInversedBy('posts', $categories);

    $this->assertDefaultJoinTable($categories, 'posts2categories', 'categories');
  }

  public function testManyToManyDoctrineMetadata_bidirectional_inverseSide() {
    $metadata = $this->assertDoctrineMetadata($this->categoryClass->getFQN());

    $posts = $this->assertAssociationMapping('posts', $metadata);

    $this->assertHasTargetEntity($this->postClass, $posts);
    $this->assertIsMappedBy('categories', $posts);

    $this->assertDefaultJoinTable($posts, 'posts2categories', 'posts');
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
