<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\System\Dir;
use Doctrine\Common\Cache\ArrayCache;
use Webforge\Code\Generator\GClass;
use Webforge\Common\JS\JSONConverter;

class ModelAssociationsOneToManyTest extends \Webforge\Doctrine\Compiler\Test\Base {

  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\Compiler';
    parent::setUp();

    $this->authorClass = $this->elevateFull('ACME\Blog\Entities\Author');
    $this->postClass = $this->elevateFull('ACME\Blog\Entities\Post');
  }

  protected function setUpPackage() {
    $this->blogPackage = self::$package;
  }

  public function testEntitiesCanExtendEachOther() {
    // the author is a user
    $authorClass = $this->elevateFull('ACME\Blog\Entities\Author');
    
    $this->assertEquals(
      'Author',
      $authorClass->getName()
    );

    $userClass = $this->elevateFull('ACME\Blog\Entities\User');
    $this->assertThatGClass($authorClass->getParent())
      ->hasParent($userClass);

    // @TODO assert that here is a real hierarchy used like: single class table, etc (annotations!)
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

  public function testSettingTheEntityOnTheManySide_AddsTheEntityOnTheOneSide() {
    $author = new Author();
    $post1 = new Post();

    $post1->setAuthor($author);
    $this->assertTrue($author->hasPost($post1));
  }

 /* public function testSettingTheEntityOnTheManySide_RemovesTheEntityOnTheOneSide() {
    $author = new Author();
    $post1 = new Post();

    $post1->setAuthor($author);

    $this->assertFalse($author->hasPost($post1));
  }*/
}