<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\System\Dir;
use Doctrine\Common\Cache\ArrayCache;

class CompilerAcceptanceTest extends \Webforge\Doctrine\Compiler\Test\Base {
  
  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\Compiler';
    parent::setUp();
  }

  public function testWritesAPlainDoctrineEntityFromJSONModel() {
    $jsonModel = <<<'JSON'
{
  "namespace": "ACME\\Blog\\Entities",

  "entities": [
    {
      "name": "User",

      "properties": {
        "id": { "type": "DefaultId" },
        "email": { "type": "String" }
      }
    }
  ]
}    
JSON;

    $this->compiler->compileModel($this->json($jsonModel), $this->psr0Directory, Compiler::PLAIN_ENTITIES);

    $gClass = $this->assertWrittenDoctrineEntity($this->psr0Directory->getFile('ACME/Blog/Entities/User.php'), 'User');

    $this->assertThatGClass($gClass)
      ->hasNamespace('ACME\Blog\Entities')
      ->hasOwnProperty('id')
      ->hasOwnProperty('email')
      ->hasMethod('getEmail')
      ->hasMethod('setEmail', array('email'))
    ;

    $this->assertDoctrineMetadata($gClass->getFQN());
  }

  protected function assertWrittenDoctrineEntity($file, $expectedClassName) {
    $this->assertFileExists($file, 'expected to get a written file for '.$expectedClassName);
    $className = '';
    $classFQN = $this->changeUniqueClassName($file, $className);

    $this->assertEquals($expectedClassName, $className, 'The written class name does not match');

    try {
      return $this->webforge->getClassElevator()->getGClass($classFQN);
    } catch (\RuntimeException $e) {
      print $contents = $file->getContents();
      throw $e;
    }
  }

  public function testThrowsModelException_WhenAssociationsAreAmbigous() {
    $jsonModel = <<<'JSON'
    {
      "namespace": "ACME\\Blog\\Entities",

      "entities": [
        {
          "name": "Post",
      
          "properties": {
            "id": { "type": "DefaultId" },
            "author": { "type": "Author" },
            "revisor": { "type": "Author", "nullable": true },
            "categories": { "type": "Collection<Category>", "isOwning": true },
            "created": { "type": "DateTime" },
            "modified": { "type": "DateTime", "nullable": true }
          },

          "constructor": ["author", "revisor"]
        },

        {
          "name": "Author",
      
          "properties": {    
            "writtenPosts": { "type": "Collection<Post>" },
            "revisionedPosts": { "type": "Collection<Post>" }
          }
        }
      ]
    }
JSON;

     $this->setExpectedException(__NAMESPACE__.'\InvalidModelException');

     try {
       $this->compiler->compileModel($this->json($jsonModel), $this->psr0Directory, Compiler::PLAIN_ENTITIES);
     } catch (InvalidModelException $e) {
       $this->assertContains('You have an ambigous definition for the association ACME\Blog\Entities\Post => ACME\Blog\Entities\Author', $e->getMessage());
       $this->assertContains('The properties: Author::writtenPosts, Author::revisionedPosts are both pointing to Author', $e->getMessage());
       print $e->getMessage();
       throw $e;
     }
  }
}
