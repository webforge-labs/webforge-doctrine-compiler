<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\System\Dir;
use Doctrine\Common\Cache\ArrayCache;
use Webforge\Doctrine\Compiler\Console\CompileCommand;
use Webforge\Code\Generator\GClass;
use Webforge\Common\JS\JSONConverter;
use Symfony\Component\Console\Tester\CommandTester;

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
    // replace current webforge called from unit tests (if)
    $autoLoader = $GLOBALS['env']['container']->getAutoLoader();
    $boot = $GLOBALS['env']['container'] = new \Webforge\Setup\BootContainer($GLOBALS['env']['root'], NULL);
    $boot->setAutoLoader($autoLoader);
    $this->webforge = $boot->getWebforge();

    $dir = $this->getPackageDir('build/package/');
    $dir->create()->wipe();
    $this->getTestDirectory('acme-blog/')->copy($dir, NULL, NULL, $recursive = TRUE);

    self::$package = $this->webforge->getPackageRegistry()->addComposerPackageFromDirectory(
      $dir
    );

    // we are the first test in the suite, so we (and only we) construct the model once
    $compileCommand = new CompileCommand('orm:compile', $this->frameworkHelper->getSystem());
    $compileCommand->injectWebforge($this->webforge);

    $application = new \Webforge\Console\Application('test application in create model test', '0.0');
    $application->add($compileCommand);

    $application->find('orm:compile');
    chdir(self::$package->getRootDirectory()->wtsPath());

    $commandTester = new CommandTester($compileCommand);
    $ret = $commandTester->execute(array(
      'command'=>$compileCommand->getName(),
      'model'=>'etc/doctrine/model.json',
      'psr0target'=>'lib/'
    ));

    $this->assertSame(0, $ret, 'compiling with command failed: '.$commandTester->getDisplay());
    
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

    $this->assertContains('@ORM\MappedSuperClass', $compiledClass->getDocBlock()->toString(), 'The docblock from the compiled entity should  include the mapped superclass annotation.');
  }

  public function testDescriptionIsCompiledIntoEntityClass() {
    $userClass = $this->elevateFull('ACME\Blog\Entities\User');

    $this->assertThatGClass($userClass)->hasDocBlock();

    $this->assertContains(
      'A basic user of the blog',
      $userClass->getDocBlock()->toString()
    );
  }
}
