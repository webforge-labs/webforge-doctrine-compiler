<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\Preg;
use org\bovigo\vfs\vfsStream;
use Webforge\Common\System\Dir;
use Webforge\Common\JS\JSONConverter;

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

      "members": {
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

    require $file;

    return $this->assertThatGClass($this->webforge->getClassElevator()->getGClass($classFQN));
  }

  protected function changeUniqueClassName($file, &$foundClassName) {
    // quick and dirty
    $newClassName = $classFQN = NULL;
    $file->writeContents(
      Preg::replace_callback(
        $contents = $file->getContents(),
        '/^(.*?)class\s+(.*?)\s+(.*)$/im',
        function($match) use (&$newClassName, &$foundClassName) {
          $foundClassName = $match[2];
          $newClassName = 'A'.uniqid().$foundClassName;

          return sprintf('%sclass %s %s', $match[1], $newClassName, $match[3]);
        }
      )
    );

    $namespace = Preg::qmatch($contents, '/^namespace\s+(.*);\s+$/mi');

    return $namespace.'\\'.$newClassName;
  }

  protected function json($string) {
    return JSONConverter::create()->parse($string);
  }
}
