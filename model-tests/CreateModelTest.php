<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\System\Dir;
use Doctrine\Common\Cache\ArrayCache;
use Webforge\Code\Generator\GClass;
use Webforge\Common\JS\JSONConverter;

require_once __DIR__.DIRECTORY_SEPARATOR.'TestReflection.php';

class CreateModelTest extends \Webforge\Doctrine\Compiler\Test\Base {

  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\Compiler';
    parent::setUp();
  }

  protected function setUpPackage() {
    $this->blogPackage = self::$package;
  }

  public function testdoCompileFixtureModel() {
    $dir = Dir::createTemporary();
    $this->getTestDirectory('acme-blog/')->copy($dir, NULL, NULL, $recursive = TRUE);

    // please note that webforge is a non-resetted class instance in the bootcontainer which is created in bootstrap.php
    self::$package = $this->webforge->getPackageRegistry()->addComposerPackageFromDirectory(
      $dir
    );

    // we are the first test in the suite, so we (and only we) construct the model once
    $jsonModel = JSONConverter::create()->parseFile(self::$package->getDirectory('etc')->sub('doctrine/')->getFile('model.json'));
    
    $this->compiler->compileModel($jsonModel, self::$package->getDirectory('lib'), Compiler::COMPILED_ENTITIES);

    // register dynamically with autoloader from composer
    $this->frameworkHelper->getBootContainer()->getAutoLoader()->add('ACME\Blog', self::$package->getDirectory('lib')->wtsPath());
  }

  /**
   * @dataProvider Webforge\Doctrine\Compiler\TestReflection::entityNames
   */
  public function testWritesAllCompiledEntitiesInModelAsAnEntityFile($entityFQN, $relativeEntityName) {
    $this->assertFileExists($this->blogPackage->getDirectory('doctrine-entities')->getFile($relativeEntityName.'.php'));
  }

  /**
   * @dataProvider Webforge\Doctrine\Compiler\TestReflection::entityNames
   */
  public function testEveryCompiledEntityHasACompiledParentClass($entityFQN) {
    $entityClass = $this->elevateFull($entityFQN);
    
    $this->assertThatGClass($entityClass)
      ->hasNamespace($this->blogPackage->getNamespace().'\Entities') // @see composer.json extra directory locations
      ->hasParent($this->getCompiledClass($entityClass));
  }

  /**
   * @dataProvider Webforge\Doctrine\Compiler\TestReflection::entityNames
   */
  public function testEveryCompiledEntityClassExistsAndIsAbstract($entityFQN) {
    $compiledClass = $this->elevateFull($this->getCompiledClass($entityFQN));

    $this->assertThatGClass($compiledClass)
      ->hasNamespace($this->blogPackage->getNamespace().'\Entities')
      ->isAbstract()
      ->hasDocBlock()
    ;

    $this->assertNotContains('@', $compiledClass->getDocBlock()->toString(), 'The docblock from the compiled entity should not include any annotation.');
  }

  protected function elevateFull($fqn) {
    if ($fqn instanceof GClass) {
      $fqn = $fqn->getFQN();
    }

    $elevator = $this->webforge->getClassElevator();

    $gClass = $elevator->getGClass($fqn);
    $elevator->elevateParent($gClass);

    return $gClass;
  }

  protected function getCompiledClass($entityClass) {
    $entityClass = new GClass($entityClass);
    $parentClass = new GClass($entityClass->getFQN());
    $parentClass->setName('Compiled'.$entityClass->getName());
    return $parentClass;
  }
}
