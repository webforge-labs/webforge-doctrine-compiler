<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;

class ModelValidatorTest extends \Webforge\Doctrine\Compiler\Test\Base {

  public function setUp() {
    $this->validator = new ModelValidator();
  }

  
  public function testLikesAValidModel() {
    $jsonModel = (object) array(
      'namespace'=>'ACME\Blog\Entities',

      'entities'=>Array(
        (object) array(
          'name'=>'User',
          'properties'=>array()
        )
      )
    );

    $this->assertValid($jsonModel);
  }

  public function testExpandsPropertyValues() {
    $jsonModel = <<<'JSON'
{
  "namespace": "ACME\\Blog\\Entities",

  "entities": [
    {
      "name": "User",

      "properties": {
        "id": { "type": "DefaultId" },
        "email": { }
      }
    }
  ]
}    
JSON;

    $model = $this->assertValid($this->json($jsonModel));

    $this->assertCount(1, $model->getEntities());
    $user = $model->getEntity('User');

    $this->assertEquals('String', $user->properties->email->type, 'Type should be expanded to string for empty member');
    $this->assertFalse($user->properties->id->nullable, 'nullable should be expanded to FALSE for not empty property');
    $this->assertFalse($user->properties->email->nullable, 'nullable should be expanded to FALSE for empty property');
  }

  public function testDoesNotLikeEmptyModels() {
    $this->assertInvalid(new stdClass());
  }

  public function testLikesShortPropertiesWithOnlyTypeAndName() {
    $jsonModel = <<<'JSON'
    {
      "namespace": "ACME\\Blog\\Entities",

      "entities": [
        {
          "name": "Category",
          "plural": "categories",

          "properties": {
            "id": "DefaultId",
            "posts": { "type": "Collection<Post>" }
          }
        }
      ]
    }
JSON;

    $this->assertValid($this->json($jsonModel));
  }

  public function testDoesNotLikeModelsWithoutNamespace() {
    $jsonModel = (object) array(
      'entities'=>array()
    );

    $this->assertInvalid($jsonModel);
  }

  public function testDoesNotLikeEntityWithEmptyExtends() {
    $this->assertInvalid($this->wrapEntity(
      (object) array(
        'name'=>'User',
        'extends'=>''
      )
    ));
  }

  public function testDoesNotLikeEntitiesThatArentExisting() {
    $jsonModel = <<<'JSON'
    {
      "namespace": "ACME\\Blog\\Entities",

      "entities": [
        {
          "name": "Author",
          "extends": "User"
        }
      ]
    }
JSON;
  
    $this->assertInvalid($this->json($jsonModel));
  }

  public function testLikesEntitiesInAnyOrder_butOnlyForValidation() {
    $jsonModel = <<<'JSON'
    {
      "namespace": "ACME\\Blog\\Entities",

      "entities": [
        {
          "name": "Author",
          "extends": "User"
        },
        {
          "name": "User",
          "extends": "User"
        }
      ]
    }
JSON;
  
    $this->assertValid($this->json($jsonModel));
  }

  public function testExpandsEntitiesFQNsAndExtends() {
    $jsonModel = <<<'JSON'
    {
      "namespace": "ACME\\Blog\\Entities",

      "entities": [
        {
          "name": "Author",
          "extends": "User"
        },
        {
          "name": "User",
          "extends": "User"
        }
      ]
    }
JSON;
    
    $model = $this->assertValid($this->json($jsonModel));

    $this->assertTrue($model->hasEntity('User'));
    $this->assertTrue($model->hasEntity('Author'));

    $author = $model->getEntity('Author');
    $user = $model->getEntity('User');

    $this->assertEquals('ACME\Blog\Entities\Author', $author->fqn);
    $this->assertEquals('ACME\Blog\Entities\User', $user->fqn);

    $this->assertSame($user, $author->extends);
  }

  public function testDoesNotLikeEntitiesAsNonObjects() {
    $jsonModel = (object) array(
      'entities'=>array(
        'name'=>'User',
        'properties'=>array()
      )
    );

    $this->assertInvalid($jsonModel);
  }

  public function testDoesNotLikeConstructorWithNonPropertiesNames() {
    $model = $this->assertInvalid($this->wrapEntity(
      $this->json('
    {
      "name": "User",

      "properties": {
        "id": { "type": "DefaultId" },
        "email": { }
      },

      "constructor": ["email", "wrong"]
    }'
      )
    ));

  }

  public function testLikesConstructorWithPropertiesNames() {
    $model = $this->assertValid($this->wrapEntity(
      $this->json('
    {
      "name": "User",

      "properties": {
        "id": { "type": "DefaultId" },
        "email": { }
      },

      "constructor": ["email"]
    }'
      )
    ));

    $user = $model->getEntity('User');

    $this->assertObjectHasAttribute('constructor', $user);
    $this->assertObjectHasAttribute("email", $user->constructor, 'email should be defined as key in constructor');
    $this->assertInternalType('object', $user->constructor->email);
    $this->assertObjectHasAttribute('name', $user->constructor->email, 'name should be defined for constructor parameter');
    $this->assertEquals('email', $user->constructor->email->name);
  }

  public function testRegressionNullableIsSettable() {
    $model = $this->assertValid($this->wrapEntity($this->json('
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
    }')));

    $post = $model->getEntity('Post');
    $this->assertTrue($post->properties->revisor->nullable, 'nullable for revisor does not match');
  }

  protected function assertInvalid(stdClass $jsonModel) {
    $this->setExpectedException(__NAMESPACE__.'\\InvalidModelException');
    $this->validator->validateModel($jsonModel);
  }

  protected function wrapEntity(stdClass $entity) {
    return (object) array(
      'namespace'=>__NAMESPACE__,
      'entities'=>array($entity)
    );
  }

  protected function wrapEntities(Array $entities) {
    return (object) array(
      'namespace'=>__NAMESPACE__,
      'entities'=>$entities
    );
  }

  /**
   * @return Webforge\Doctrine\Compiler\Model
   */
  protected function assertValid(stdClass $jsonModel) {
    $this->assertInstanceOf(__NAMESPACE__.'\\Model', $model = $this->validator->validateModel($jsonModel));
    return $model;
  }
}
