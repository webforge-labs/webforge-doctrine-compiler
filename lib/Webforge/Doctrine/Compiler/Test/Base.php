<?php

namespace Webforge\Doctrine\Compiler\Test;

use Webforge\Doctrine\Compiler\Compiler;
use Webforge\Doctrine\Compiler\EntityGenerator;
use Webforge\Doctrine\Compiler\Inflector;
use Webforge\Doctrine\Compiler\ModelValidator;
use Webforge\Doctrine\Compiler\EntityMappingGenerator;
use Webforge\Doctrine\Annotations\Writer as AnnotationsWriter;
use org\bovigo\vfs\vfsStream;
use Webforge\Common\System\Dir;
use Webforge\Common\Preg;
use Webforge\Common\JS\JSONConverter;
use Mockery as m;

class Base extends \Webforge\Doctrine\Test\SchemaTestCase {

  protected $webforge;
  protected $compiler;
  protected $psr0Directory, $testPackage;

  public static $schemaCreated = TRUE;

  public function setUp() {
    $this->virtualPackageDirectory = $this->getVirtualDirectory('packageroot');
    parent::setUp();

    $this->webforge = $this->frameworkHelper->getWebforge();

    // fake a local package in the virtual dir
    $this->testPackage = $this->webforge->getPackageRegistry()->addComposerPackageFromDirectory(
      $this->virtualPackageDirectory
    );
    $this->psr0Directory = $this->testPackage->getDirectory('lib');


    // inject classmapper (see unique file hack)
    $this->mapper = m::mock('Webforge\Code\Generator\ClassFileMapper');
    $this->webforge->setClassFileMapper($this->mapper);

    $this->compiler = new Compiler(
      $this->webforge->getClassWriter(), 
      new EntityGenerator($inflector = new Inflector, new EntityMappingGenerator($writer = new AnnotationsWriter, $inflector)),
      new ModelValidator
    );
  }

  protected function initEntitiesPaths() {
    $this->entitiesPaths = array((string) $this->virtualPackageDirectory->sub('lib/ACME/Blog/Entities')->create());
    return $this->entitiesPaths;
  }

  protected function getVirtualDirectory($name) {
    $dir = vfsStream::setup($name);

    vfsStream::copyFromFileSystem((string) $this->getTestDirectory('acme-blog/'), $dir, 1024*8);

    return new Dir(vfsStream::url($name).'/');
  }

  protected function changeUniqueClassName($file, &$foundClassName) {
    // quick and dirty
    $newClassName = NULL;
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
    $changedFQN = $namespace.'\\'.$newClassName;

    // duplicate for doctrine
    $dupl = clone $file;
    $dupl->setName($newClassName);
    $file->copy($dupl);

    $this->doctrineFile = $dupl;
    require $dupl;

    $this->mapper->shouldReceive('getFile')->with($changedFQN)->andReturn($file);

    return $changedFQN;
  }

  protected function json($string) {
    return JSONConverter::create()->parse($string);
  }
}
