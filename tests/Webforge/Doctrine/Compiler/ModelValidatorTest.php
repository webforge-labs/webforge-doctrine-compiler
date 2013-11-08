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

  public function testDoesNotLikeEntitiesAsNonObjects() {
    $jsonModel = (object) array(
      'entities'=>array(
        'name'=>'User',
        'properties'=>array()
      )
    );

    $this->assertInvalid($jsonModel);
  }

  protected function assertInvalid(stdClass $jsonModel) {
    $this->setExpectedException(__NAMESPACE__.'\\InvalidModelException');
    $this->validator->validateModel($jsonModel);
  }

  /**
   * @return Webforge\Doctrine\Compiler\Model
   */
  protected function assertValid(stdClass $jsonModel) {
    $this->assertInstanceOf(__NAMESPACE__.'\\Model', $model = $this->validator->validateModel($jsonModel));
    return $model;
  }
}
