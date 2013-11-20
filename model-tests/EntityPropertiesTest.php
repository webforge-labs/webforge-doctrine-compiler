<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\System\Dir;
use Doctrine\Common\Cache\ArrayCache;
use Webforge\Code\Generator\GClass;
use Webforge\Common\JS\JSONConverter;

class EntityPropertiesTest extends \Webforge\Doctrine\Compiler\Test\ModelBase {

  public function testEntityDoesNotHaveThePropertiesFromTheCompiledEntity() {
    $authorClass = $this->elevateFull('ACME\Blog\Entities\Author');
    
    $this->assertThatGClass($authorClass)
      ->hasNotOwnProperty('writtenPosts')
      ->hasNotOwnProperty('revisionedPosts')
    ;

    $this->assertThatGClass($authorClass->getParent())
      ->hasOwnProperty('writtenPosts')
      ->hasOwnProperty('revisionedPosts')
    ;
  }

  public function testSimpleColumnsAreInDoctrineMetadata() {
    $email = $this->assertMetadataField('User', 'email');

    $this->assertEquals('string', $email['type']);
  }

  public function testStringColumnsCanHaveALength() {
    $email = $this->assertMetadataField('User', 'email');

    $this->assertEquals(210, $email['length'], 'length from email is not matched');
  }

  public function testFieldsWithSimpleTypesCanBeNullable() {
    $updated = $this->assertMetadataField('Post', 'modified');

    $this->assertTrue($updated['nullable'], 'nullable from post::modified does not match');
  }

}
