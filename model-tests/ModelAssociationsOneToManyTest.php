<?php

namespace Webforge\Doctrine\Compiler;

use ACME\Blog\Entities\Author;
use ACME\Blog\Entities\Post;
use ACME\Blog\Entities\User;

class ModelAssociationsOneToManyTest extends \Webforge\Doctrine\Compiler\Test\ModelBase {

  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\Compiler';
    parent::setUp();

    $this->authorClass = $this->elevateFull('ACME\Blog\Entities\Author');
    $this->postClass = $this->elevateFull('ACME\Blog\Entities\Post');
  }

  public function testHasAllSettersGettersAddersRemoversAndCheckers() {
    // one (written)post has one author
    // one author has many (written)posts

    $this->assertThatGClass($this->authorClass->getParent())

      ->hasProperty('writtenPosts')
        ->isProtected()
      ->hasMethod('getWrittenPosts')
        ->isPublic()
      ->hasMethod('setWrittenPosts', array('writtenPosts'))
        ->isPublic()
      ->hasMethod('addWrittenPost', array('writtenPost'))
      ->hasMethod('removeWrittenPost', array('writtenPost'))
      ->hasMethod('hasWrittenPost', array('writtenPost'))

      ->hasProperty('revisionedPosts')
        ->isProtected()
      ->hasMethod('setRevisionedPosts', array('revisionedPosts'))
        ->isPublic()
      ->hasMethod('getRevisionedPosts')
        ->isPublic()
      ->hasMethod('addRevisionedPost', array('revisionedPost'))
      ->hasMethod('removeRevisionedPost', array('revisionedPost'))
      ->hasMethod('hasRevisionedPost', array('revisionedPost'))
    ;

    $this->assertThatGClass($this->postClass->getParent())
      ->hasProperty('author')
        ->isProtected()
      ->hasMethod('setAuthor', array('author'))
        ->isPublic()
      ->hasMethod('getAuthor')
        ->isPublic()
      ->hasNotMethod('addAuthor')
      ->hasNotMethod('removeAuthor')
    ;
  }

  public function testConstructorIsGeneratedWithPropertiesAndNullablePropertiesAreOptional() {

    $method = $this->assertThatGClass($this->postClass->getParent())
      ->hasMethod('__construct', array('author', 'revisor'))
        ->isPublic()
        ->get();

    $revisorParam = $method->getParameterByName('revisor');
    $this->assertTrue($revisorParam->hasDefault(), 'revisor should be optional');
    $authorParam = $method->getParameterByName('author');
    $this->assertFalse($authorParam->hasDefault(), 'author shouldnt be optional');
  }

  public function testProvidingTheEntityOnTheManySideToConstructor_AddsTheEntityOnTheOneSide() {
    $author = new Author();
    $post1 = new Post($author);

    $this->assertSame($author, $post1->getAuthor($post1));
    $this->assertTrue($author->hasWrittenPost($post1));
  }

  public function testOneToManyDoctrineMetadata() {
    $authorMetadata = $this->assertDoctrineMetadata($this->authorClass);

    $writtenPosts = $this->assertAssociationMapping('writtenPosts', $authorMetadata);

    $this->assertHasTargetEntity($this->postClass, $writtenPosts);
    $this->assertIsMappedBy('author', $writtenPosts);
  }

  public function testManyToOneDoctrineMetadata() {
    $postMetadata = $this->em->getMetadataFactory()->getMetadataFor($this->postClass->getFQN());

    $author = $this->assertAssociationMapping('author', $postMetadata);

    $this->assertHasTargetEntity($this->authorClass, $author);
    $this->assertIsInversedBy('writtenPosts', $author);
  }
}
