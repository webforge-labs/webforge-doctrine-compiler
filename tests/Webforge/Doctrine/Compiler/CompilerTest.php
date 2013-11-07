<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\System\Dir;


class CompilerTest extends \Webforge\Doctrine\Compiler\Test\Base {
  
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

    $this->assertWrittenDoctrineEntity($this->psr0Directory->getFile('ACME/Blog/Entities/User.php'), 'User')
      ->hasNamespace('ACME\Blog\Entities')
      ->hasOwnProperty('id')
      ->hasOwnProperty('email')
      ->hasMethod('getEmail')
      ->hasMethod('setEmail', array('email'))
    ;
  }

  protected function assertWrittenDoctrineEntity($file, $expectedClassName) {
    $this->assertFileExists($file, 'expected to get a written file for '.$expectedClassName);
    $className = '';
    $classFQN = $this->changeUniqueClassName($file, $className);

    $this->assertEquals($expectedClassName, $className, 'The written class name does not match');


    try {
      return $this->assertThatGClass($this->webforge->getClassElevator()->getGClass($classFQN));
    } catch (\RuntimeException $e) {
      print $contents = $file->getContents();
      throw $e;
    }
  }
}
