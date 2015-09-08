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
  
    /* this is another view of the same problem:
    $this->assertCompilerThrowsModelException($jsonModel, array(
      'You have an ambigous definition for the association ACME\Blog\Entities\Author => ACME\Blog\Entities\Post',
      'The properties: Post::author, Post::revisor are pointing to Author'
    ));
    */

    $this->assertCompilerThrowsModelException($jsonModel, array(
      'You have an ambigous definition for the association ACME\Blog\Entities\Post => ACME\Blog\Entities\Author',
      'The properties: Author::writtenPosts, Author::revisionedPosts are pointing to Post'
    ));
  }

  public function testThrowsModelException_WhenINVERSEAssociationsAreAmbigous() {
    $jsonModel = <<<'JSON'
    {
      "namespace": "ACME\\Blog\\Entities",

      "entities": [
        {
          "name": "Post",
      
          "properties": {
            "id": { "type": "DefaultId" },
            "categories": { "type": "Collection<Category>", "isOwning": true, "orderBy": { "position":"ASC" }, "cascade": ["persist", "remove"] },
            "topCategory": { "type": "Category" }

          }
        },

        {
          "name": "Category",

          "properties": {
            "id": "DefaultId",
            "label": { "type": "String" },
            "posts": { "type": "Collection<Post>" }
          }
        }
      ]
    }
JSON;
  
    $this->assertCompilerThrowsModelException($jsonModel, array(
      'You have an ambigous definition for the association ACME\Blog\Entities\Category => ACME\Blog\Entities\Post',
      'The properties: Post::categories, Post::topCategory are pointing to Category',
      'set "relation" in the definition of Post::categories or Post::topCategory to the name of the property you want'
    ));
  }

  public function testThrowsIfARelationIsReferencedThatIsNotExisting() {
    $jsonModel = <<<'JSON'
    {
      "namespace": "ACME\\Blog\\Entities",

      "entities": [
        {
          "name": "Post",
      
          "properties": {
            "id": { "type": "DefaultId" }
          }
        },

        {
          "name": "Category",

          "properties": {
            "id": "DefaultId",

            "posts": { "type": "Collection<Post>", "relation": "cats" }
          }
        }
      ]
    }
JSON;

    $this->assertCompilerThrowsModelException($jsonModel, array(
      'You are referencing a non existing property Post::cats in the relation of Category::posts'
    ));
  }

  public function testCanCompile_WhenUnidirectorionalAssociationsAreAmbigousInType() {
    /*
     First i though we need a relationType in tags here because its not determinable if this is a OneToMany OneToOne or ManyToMany relationship, but:
     OneToOne is not possible because its Collection<Tag>
     OneToMany is not possible because Tags MUST be the owning side than (and would refer with Tag::posts to it)
     so only ManyToMany unidirectional is possible
    */

    $jsonModel = <<<'JSON'
    {
      "namespace": "ACME\\Blog\\Entities",

      "entities": [
        {
          "name": "Post",
      
          "properties": {
            "id": { "type": "DefaultId" },
            "tags": { "type": "Collection<Tag>", "isOwning": true }
          }
        },

        {
          "name": "Tag",
      
          "properties": {
            "id": { "type": "DefaultId" },
            "label": { "type": "String" }
          }
        }
      ]
    }
JSON;

    $this->compiler->compileModel($this->json($jsonModel), $this->psr0Directory, Compiler::PLAIN_ENTITIES);
  }

  protected function assertCompilerThrowsModelException($jsonModel, Array $containments) {
    $this->assertNotEmpty($containments);
    $this->setExpectedException(__NAMESPACE__.'\InvalidModelException');

    try {
      $this->compiler->compileModel($this->json($jsonModel), $this->psr0Directory, Compiler::PLAIN_ENTITIES);
    } catch (InvalidModelException $e) {
      foreach ($containments as $string) {
        $this->assertContains($string, $e->getMessage());
      }
      throw $e;
    }
  }
}
