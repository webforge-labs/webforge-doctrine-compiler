<?php

namespace Webforge\Doctrine\Compiler;

use ACME\Blog\Entities\Author;
use ACME\Blog\Entities\Post;
use Webforge\Common\DateTime\DateTime;

class JMSSerializationTest extends \Webforge\Doctrine\Compiler\Test\ModelBase {

  public function setUp() {
    parent::setUp();

    $builder = new \Webforge\Serializer\SerializerBuilder;
    $this->serializer = $builder->getSerializer();

    $this->author = new Author('p.scheit@ps-webforge.com');
    $this->author->setId(7);

    $this->post = new Post($this->author);
    $this->post->setId(11);
    $this->post->setActive(true);
    $this->post->setCreated($this->now = DateTime::now());

    $this->nowExport = (object) array(
      'date'=>$this->now->format('Y-m-d H:i:s'),
      'timezone'=>$this->now->getTimezone()->getName()
    );
  }

  public function testThePostPrimitveTypesAreMappedCorectly() {
    $factory = $this->serializer->getMetadataFactory();
    $metadata = $factory->getMetadataForClass(get_class($this->post));

    // this is hardcoded in the manyToOne etc mappings:
    $this->assertSerializerPropertyType(get_class($this->author), 'revisor', $metadata);
    $this->assertSerializerPropertyType('ArrayCollection', 'tags', $metadata);

    // this are primitives equal to the doctrine type
    $this->assertSerializerPropertyType('boolean', 'active', $metadata);
    $this->assertSerializerPropertyType('WebforgeDateTime', 'modified', $metadata);
    $this->assertSerializerPropertyType('string', 'content', $metadata);

    // depends on detecting the SerializationType interface (all others are identical to doctrine export types)
    $this->assertSerializerPropertyType('double', 'relevance', $metadata); // float = double in serializer
  }

  public function testAnAuthorCanBeSerializedWithoutSomeChildEntities() {
    $this->assertThatObject($this->serialize($this->author))
      ->property('email')->is('p.scheit@ps-webforge.com')->end()
      ->property('id')->is($this->equalTo(7))->end()
      ->property('writtenPosts')->isArray()->end()
      ->property('revisionedPosts')->isArray()->end()
    ;
  }


  public function testAPostCanBeSerialized() {
    $this->assertThatObject($this->serialize($this->post))
      ->property('id')->is($this->equalTo(11))->end()
      ->property('author')->isObject()
        ->property('email')->is('p.scheit@ps-webforge.com')->end()
        ->property('id')->is($this->equalTo(7))->end()
        ->property('writtenPosts')->isArray()->end()
        ->property('revisionedPosts')->isArray()->end()
      ->end()
      ->property('active')->is($this->identicalTo(true))->end()
      ->property('created')->is($this->equalTo($this->nowExport))->end();
    ;
  }

  public function testAnAuthorCanBeDeSerialized() {
    $jsonstring = json_encode(
      $json = (object) array(
        'email'=>'p.scheit@ps-webforge.com',
        'id'=>7,
        'writtenPosts'=>array(),
        'revisionedPosts'=>array()
      )
    );

    $author = $this->serializer->deserialize($jsonstring, 'ACME\Blog\Entities\Author', 'json');

    $this->assertEquals($json->email, $author->getEmail());
    $this->assertEquals($json->id, $author->getId());
    $this->assertEquals($json->writtenPosts, $author->getWrittenPosts()->toArray());
    $this->assertEquals($json->revisionedPosts, $author->getRevisionedPosts()->toArray());
  }

  public function testTheDeserializationFromPostIsBidirectional() {
    $json = $this->serializer->serialize($this->post, 'json');

    $post = $this->serializer->deserialize(json_encode($json), 'ACME\Blog\Entities\Post', 'json');

    $this->assertEquals($this->post->getCreated(), $post->getCreated());
    $this->assertEquals($this->post->getId(), $post->getId());
    $this->assertEquals($this->post->getAuthor()->getId(), $post->getAuthor()->getId());
    $this->assertEquals($this->post->getAuthor()->getEmail(), $post->getAuthor()->getEmail());
    $this->assertEquals($this->post->getActive(), $post->getActive());

    /* we cannot test the full author is equivalent here, because:
      when creating the post the post is added to the author in writtenPosts. But this is a recursive collection to the serialization post itself, so json cannot encode this
      so that it isn deserialized
    */
  }

  protected function serialize($object) {
    return $this->serializer->serialize($object, 'json');
  }

}
