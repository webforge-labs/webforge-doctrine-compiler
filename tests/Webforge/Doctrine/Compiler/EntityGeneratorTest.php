<?php

namespace Webforge\Doctrine\Compiler;

class EntityGeneratorTest extends \Webforge\Doctrine\Compiler\Test\Base {
  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\EntityGenerator';
    parent::setUp();
    // setup siehe base
  }

  public function testUndefinedIndexOwningError() {
    $json = <<<'JSON'
{
  "namespace": "SSC\\Entities",

  "entities": [
    {
      "name": "Page",

      "properties": {
        "id": { "type": "DefaultId" },

        "contentStream": { "type": "ContentStream" }
      }
    },

    {
      "name": "ContentStream",

      "properties": {
        "id": { "type": "DefaultId" },

        "page": { "type": "Page", "nullable": true }
      }
    }
  ]
}
JSON
    ;

    $this->setExpectedException(__NAMESPACE__.'\InvalidModelException');

    try {
      $this->entityGenerator->generate($this->getModel($json));

    } catch (InvalidModelException $e) {
      $this->assertContains('no owning side for the association ContentStream::page <=> Page::contentStream', $e->getMessage());
      $this->assertContains('OneToOne', $e->getMessage());
      $this->assertContains('to set isOwning', $e->getMessage());
      throw $e;
    }
  }

  protected function getModel($json) {
    if (is_string($json)) {
      $json = json_decode($json);
    }

    $this->validator = new ModelValidator();
    return $model = $this->validator->validateModel($json);
  }
}
