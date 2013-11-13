<?php

namespace Webforge\Doctrine\Compiler;

use Doctrine\ORM\Tools\SchemaValidator;

class ModelDCValidationTest extends \Webforge\Doctrine\Compiler\Test\Base {

  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\Compiler';
    parent::setUp();

    $this->validator = new SchemaValidator($this->em);
  }

  protected function setUpPackage() {
    $this->blogPackage = self::$package;
  }

  public function testSchemaIsValid() {
    $errors = $this->validator->validateMapping();
    $this->assertCount(0, $errors, 'There were errors with the generated schema: '.print_r($errors, true));
  }
}
