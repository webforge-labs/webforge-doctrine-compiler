<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Common\JS\JSONConverter;

class ModelValidatorTest extends \Webforge\Doctrine\Compiler\Test\Base
{
    public function setUp()
    {
        $this->validator = new ModelValidator();
    }


    public function testLikesAValidModel()
    {
        $jsonModel = (object) array(
        'namespace' => 'ACME\Blog\Entities',

        'entities' => array(
        (object) array(
          'name' => 'User',
          'properties' => array()
        )
        )
        );

        $this->assertValid($jsonModel);
    }

    public function testExpandsPropertyValues()
    {
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

        $this->assertEquals('String', $user->properties->email->type->getName(), 'Type should be expanded to stringType for empty member');
        $this->assertFalse($user->properties->id->nullable, 'nullable should be expanded to FALSE for not empty property');
        $this->assertFalse($user->properties->email->nullable, 'nullable should be expanded to FALSE for empty property');
    }

    public function testDoesNotLikeEmptyModels()
    {
        $this->assertInvalid(new stdClass());
    }

    public function testDoesNotLikeEmptyModelsWithoutEntities()
    {
        $this->assertInvalid((object) array(
        'namespace' => 'ACME\Blog\Enttities'
        ));
    }

    public function testLikesShortPropertiesWithOnlyTypeAndName()
    {
        $jsonModel = <<<'JSON'
    {
      "namespace": "ACME\\Blog\\Entities",

      "entities": [
        {
          "name": "Category",
          "plural": "categories",

          "properties": {
            "id": "DefaultId",
            "label": { "type": "String" }
          }
        }
      ]
    }
JSON;

        $this->assertValid($this->json($jsonModel));
    }

    public function testDoesNotLikeModelsWithoutNamespace()
    {
        $jsonModel = (object) array(
        'entities' => array()
        );

        $this->assertInvalid($jsonModel);
    }

    public function testDoesNotLikeEntityWithEmptyExtends()
    {
        $this->assertInvalid($this->wrapEntity(
            (object) array(
            'name' => 'User',
            'extends' => ''
            )
        ));
    }

    public function testDoesNotLikeEntityWithEmptyName()
    {
        $this->assertInvalid($this->wrapEntity(
            (object) array(
            'name' => '',
            'extends' => ''
            )
        ));
    }

    public function testDoesNotLikeEntityWithoutName()
    {
        $this->assertInvalid($this->wrapEntity(
            (object) array(
            "properties" => (object) array(
            )
            )
        ));
    }

    public function testDoesNotLikeEntityAsArray()
    {
        $this->assertInvalid($this->wrapEntity(
            array(
            "name" => "User",
            "properties" => (object) array(
            )
            )
        ));
    }

    public function testDoesNotLikeNonObjectProperties()
    {
        $this->assertInvalid($this->wrapEntity(
            (object) array(
            "name" => "User",
            "properties" => (object) array(
            "email" => true
            )
            )
        ));
    }

    public function testDoesNotLikeNonExistingTypes()
    {
        $this->assertInvalid($this->wrapEntity(
            (object) array(
            "name" => "User",
            "properties" => (object) array(
            "email" => (object) array(
            'type' => 'nonsense'
            )
            )
            )
        ));
    }

    public function testDoesNotLikeMalFormedTypes()
    {
        $this->assertInvalid($this->wrapEntity(
            (object) array(
            "name" => "User",
            "properties" => (object) array(
            "email" => (object) array(
            'type' => 'Collection<wrong'
            )
            )
            )
        ));
    }

    public function testDoesNotLikeCollectionTypesWithWrongEntityName()
    {
        $this->assertInvalid($this->wrapEntity(
            (object) array(
            "name" => "User",
            "properties" => (object) array(
            "email" => (object) array(
            'type' => 'Collection<NonExistingEntity>'
            )
            )
            )
        ));
    }

    public function testDoesNotLikeEntitiesThatArentExisting()
    {
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

    public function testLikesEntitiesInAnyOrder_butOnlyForValidation()
    {
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

    public function testExpandsEntitiesFQNsAndExtends()
    {
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


    public function testExpandsEntitiesFQNsWithSubNamespaces()
    {
        $jsonModel = <<<'JSON'
    {
      "namespace": "ACME\\Blog\\Entities",

      "entities": [
        {
          "name": "ContentStream\\Paragraph"
        }
      ]
    }
JSON;

        $model = $this->assertValid($this->json($jsonModel));

        $this->assertTrue($model->hasEntity('ContentStream\Paragraph'));

        $p = $model->getEntity('ContentStream\Paragraph');

        $this->assertEquals('ACME\Blog\Entities\ContentStream\Paragraph', $p->fqn);
    }

    public function testDoesNotLikeEntitiesAsNonObjects()
    {
        $jsonModel = (object) array(
        'entities' => array(
        'name' => 'User',
        'properties' => array()
        )
        );

        $this->assertInvalid($jsonModel);
    }

    public function testDoesNotLikeConstructorWithNonPropertiesNames()
    {
        $model = $this->assertInvalid($this->wrapEntity(
            $this->json('
    {
      "name": "User",

      "properties": {
        "id": { "type": "DefaultId" },
        "email": { }
      },

      "constructor": ["email", "wrong"]
    }')
        ));
    }

    public function testLikesConstructorWithPropertiesNames()
    {
        $model = $this->assertValid($this->wrapEntity(
            $this->json('
    {
      "name": "User",

      "properties": {
        "id": { "type": "DefaultId" },
        "email": { }
      },

      "constructor": ["email"]
    }')
        ));

        $user = $model->getEntity('User');

        $this->assertObjectHasAttribute('constructor', $user);
        $this->assertObjectHasAttribute("email", $user->constructor, 'email should be defined as key in constructor');
        $this->assertInternalType('object', $user->constructor->email);
        $this->assertObjectHasAttribute('name', $user->constructor->email, 'name should be defined for constructor parameter');
        $this->assertEquals('email', $user->constructor->email->name);
    }

    public function testLikesConstructorWithDefinitions()
    {
        $model = $this->assertValid($this->wrapEntity(
            $this->json('
    {
      "name": "User",

      "properties": {
        "id": { "type": "DefaultId" },
        "email": { }
      },

      "constructor": [
        { "name": "email", "defaultValue": "\'nobody@example.com\'" }
      ]
    }')
        ));

        $user = $model->getEntity('User');

        $this->assertObjectHasAttribute('constructor', $user);
        $this->assertObjectHasAttribute("email", $user->constructor, 'email should be defined as key in constructor');
        $this->assertInternalType('object', $user->constructor->email);
        $this->assertObjectHasAttribute('name', $user->constructor->email, 'name should be defined for constructor parameter');
        $this->assertObjectHasAttribute('defaultValue', $user->constructor->email, 'defaultValue from email should be defined for constructor parameter');
        $this->assertEquals('email', $user->constructor->email->name);
        $this->assertEquals("'nobody@example.com'", $user->constructor->email->defaultValue);
    }

    public function testDoesNotLikesConstructorWithoutName()
    {
        $this->assertInvalid($this->wrapEntity(
            $this->json('
    {
      "name": "User",

      "properties": {
        "id": { "type": "DefaultId" },
        "email": { }
      },

      "constructor": [
        { "ame": "email" }
      ]
    }')
        ), 'Invalid object as constructor argument');
    }

    public function testDoesNotLikesConstructorWithWrongType()
    {
        $this->assertInvalid($this->wrapEntity(
            $this->json('
    {
      "name": "User",

      "properties": {
        "id": { "type": "DefaultId" },
        "email": { }
      },

      "constructor": [
        7
      ]
    }')
        ), 'Invalid value-type as constructor argument');
    }


    public function testRegressionNullableIsSettable()
    {
        $model = $this->validateAcceptanceModel();

        $post = $model->getEntity('Post');
        $this->assertTrue($post->properties->revisor->nullable, 'nullable for revisor does not match');
    }

    public function testCreatesEntityReferencesForTheParsedModel()
    {
        $model = $this->validateAcceptanceModel();

        $post = $model->getEntity('Post');
        $this->assertIsReference($post->properties->author->type, 'post::author type does not match');

        $author = $model->getEntity('Author');
        $this->assertIsCollectionReference($author->properties->writtenPosts->type, 'author::writtenPosts type does not match');
    }

    protected function assertInvalid(stdClass $jsonModel, $message = null)
    {
        $this->setExpectedException(__NAMESPACE__ . '\\InvalidModelException', $message);
        $this->validator->validateModel($jsonModel);
    }

    protected function wrapEntity($entity)
    {
        return (object) array(
        'namespace' => __NAMESPACE__,
        'entities' => array($entity)
        );
    }

    protected function wrapEntities(array $entities)
    {
        return (object) array(
        'namespace' => __NAMESPACE__,
        'entities' => $entities
        );
    }

  /**
   * @return Webforge\Doctrine\Compiler\Model
   */
    protected function assertValid(stdClass $jsonModel)
    {
        $this->assertInstanceOf(__NAMESPACE__ . '\\Model', $model = $this->validator->validateModel($jsonModel));
        return $model;
    }

    protected function validateAcceptanceModel()
    {
        $jsonModel = JSONConverter::create()->parseFile($this->getTestDirectory('acme-blog/etc/doctrine')->getFile('model.json'));

        return $this->assertValid($jsonModel);
    }

    protected function assertIsReference($object, $msg = '')
    {
        $this->assertInstanceOf(__NAMESPACE__ . '\EntityReference', $object, $msg);
    }

    protected function assertIsCollectionReference($object, $msg = '')
    {
        $this->assertInstanceOf(__NAMESPACE__ . '\EntityCollectionReference', $object, $msg);
    }
}
