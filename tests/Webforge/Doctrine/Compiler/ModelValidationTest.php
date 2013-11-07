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
          'members'=>array()
        )
      )
    );

    $this->assertValid($jsonModel);
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
        'members'=>array()
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
