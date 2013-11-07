<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;

class ModelValidationTest extends \Webforge\Doctrine\Compiler\Test\Base {
  
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

    $jsonModel = $this->assertValid($this->json($jsonModel));

    $this->assertObjectHasAttribute('entities', $jsonModel);
    $this->assertArrayHasKey(0, $jsonModel->entities);

    $user = $jsonModel->entities[0];

    $this->assertEquals('String', $user->properties->email->type, 'Type should be expanded to string for empty member');
    $this->assertFalse($user->properties->id->nullable, 'nullable should be expanded to FALSE for not empty property');
    $this->assertFalse($user->properties->email->nullable, 'nullable should be expanded to FALSE for empty property');
  }

  public function testDoesNotLikeEmptyModels() {
    $this->assertInvalid(new stdClass());
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
    $this->compiler->validateModel($jsonModel);
  }

  protected function assertValid(stdClass $jsonModel) {
    return $this->compiler->validateModel($jsonModel);
  }
}
