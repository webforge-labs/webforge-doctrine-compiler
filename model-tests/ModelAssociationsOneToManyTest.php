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
    $author = new Author('p.scheit@ps-webforge.com');
    $post1 = new Post($author);

    $this->assertSame($author, $post1->getAuthor($post1));
    $this->assertTrue($author->hasWrittenPost($post1));
  }

  public function testSettingTheManySideUpdatesTheOneSide() {
    $author = new Author('p.scheit@ps-webforge.com');
    $post1 = new Post($author);

    $newAuthor = new Author('ik@ps-webforge.com');
    $post1->setAuthor($newAuthor);

    $this->assertSame($newAuthor, $post1->getAuthor());
    $this->assertTrue($newAuthor->hasWrittenPost($post1));
  }

  public function testSettingTheManySideUpdatesTheOneSideAndThePreviousOneSide() {
    $oldAuthor = new Author('p.scheit@ps-webforge.com');
    $post1 = new Post($oldAuthor);
    $this->assertTrue($oldAuthor->hasWrittenPost($post1));

    $newAuthor = new Author('ik@ps-webforge.com');
    $post1->setAuthor($newAuthor);
    $this->assertFalse($oldAuthor->hasWrittenPost($post1), 'oldAuthor should not have the post listed anymore');
  }

  public function testSettingTheManySideToNULLRemovesFromOneSide() {
    $oldRevisor = new Author('ik@ps-webforge.com');
    $post1 = new Post(new Author('p.scheit@ps-webforge.com'));
    $post1->setRevisor($oldRevisor);
    $this->assertSame($oldRevisor, $post1->getRevisor());
    $this->assertTrue($oldRevisor->hasRevisionedPost($post1));

    $post1->setRevisor(NULL);
    $this->assertNull($post1->getRevisor());
    $this->assertFalse($oldRevisor->hasRevisionedPost($post1), 'oldRevisor should not have the post listed anymore');
  }

  public function testOneToManyDoctrineMetadata() {
    $authorMetadata = $this->assertDoctrineMetadata($this->authorClass);

    $writtenPosts = $this->assertAssociationMapping('writtenPosts', $authorMetadata);

    $this->assertHasTargetEntity($this->postClass, $writtenPosts);
    $this->assertIsMappedBy('author', $writtenPosts);
  }

  public function testManyToOneDoctrineMetadata() {
    $postMetadata = $this->em->getMetadataFactory()->getMetadataFor($this->postClass->getFQN());

    $authorAssoc = $this->assertAssociationMapping('author', $postMetadata);

    $this->assertHasTargetEntity($this->authorClass, $authorAssoc);
    $this->assertJoinColumnNotNullable($authorAssoc);
    $this->assertIsInversedBy('writtenPosts', $authorAssoc);
  }

  public function testManyToOneWithSubNamespacesMetadata() {
    $entryClass = $this->elevateFull('ACME\Blog\Entities\ContentStream\Entry');
    $streamClass = $this->elevateFull('ACME\Blog\Entities\ContentStream\Stream');
    $metadata = $this->assertDoctrineMetadata($streamClass);

    $entries = $this->assertAssociationMapping('entries', $metadata);

    $this->assertHasTargetEntity($entryClass, $entries);
  }

  public function testManyToOneUnidirectionalDoctrineMetadata() {
    $blockClass = $this->elevateFull('ACME\Blog\Entities\ContentStream\TextBlock');
    $paragraphClass = $this->elevateFull('ACME\Blog\Entities\ContentStream\Paragraph');
    $metadata = $this->assertDoctrineMetadata($blockClass);

    $p1 = $this->assertAssociationMapping('paragraph1', $metadata);

    $this->assertHasTargetEntity($paragraphClass, $p1);
    $this->assertIsUnidirectional($p1);
  }

  public function testManyToOneImplicitUnidirectionalDoctrineMetadata() {
    $blockClass = $this->elevateFull('ACME\Blog\Entities\ContentStream\TextBlock');
    $paragraphClass = $this->elevateFull('ACME\Blog\Entities\ContentStream\Paragraph');
    $metadata = $this->assertDoctrineMetadata($blockClass);

    $p1 = $this->assertAssociationMapping('paragraph1', $metadata);

    $this->assertHasTargetEntity($paragraphClass, $p1);
    $this->assertIsUnidirectional($p1);
  }

  public function testManyToOneExplicitUnidirectionalDoctrineMetadata() {
    $blockClass = $this->elevateFull('ACME\Blog\Entities\ContentStream\TextBlock');
    $paragraphClass = $this->elevateFull('ACME\Blog\Entities\ContentStream\Paragraph');
    $metadata = $this->assertDoctrineMetadata($blockClass);

    $p2 = $this->assertAssociationMapping('paragraph2', $metadata);

    $this->assertHasTargetEntity($paragraphClass, $p2);
    $this->assertIsUnidirectional($p2);
  }


  public function testOnDeleteIsPutIntoMetadataOfManySide() {
    $pageClass = $this->elevateFull('ACME\Blog\Entities\Page');
    $streamClass = $this->elevateFull('ACME\Blog\Entities\ContentStream\Stream');
    $metadata = $this->assertDoctrineMetadata($streamClass);

    $page = $this->assertAssociationMapping('page', $metadata);

    $this->assertHasTargetEntity($pageClass, $page);
    $this->assertArrayHasKey('onDelete',$page['joinColumns'][0], 'onDelete value should be set for joinColumn of ContentStream::$page');
    $this->assertEquals('cascade', $page['joinColumns'][0]['onDelete'], 'onDelete value from page');
  }

  public function testManyToOne_Unidirectional_AmbigousMapping() {
    $metadata = $this->assertDoctrineMetadata($this->postClass);
    $categoryClass = $this->elevateFull('ACME\Blog\Entities\Category');

    $assoc = $this->assertAssociationMapping('topCategory', $metadata);

    $this->assertHasTargetEntity($categoryClass, $assoc);
    $this->assertIsUnidirectional($assoc);
  }
}
