<?php

namespace Webforge\Doctrine\Compiler\Test;

use Webforge\Doctrine\Compiler\Compiler;
use org\bovigo\vfs\vfsStream;
use Webforge\Common\System\Dir;
use Webforge\Common\Preg;
use Webforge\Common\JS\JSONConverter;
use Mockery as m;

class Base extends \Webforge\Code\Test\Base {

  protected $webforge;
  protected $compiler;
  protected $psr0Directory, $testPackage;

  public function setUp() {
    parent::setUp();

    $this->webforge = $this->frameworkHelper->getWebforge();
    $this->testPackage = $this->webforge->getPackageRegistry()->addComposerPackageFromDirectory(
      $this->getVirtualDirectory('packageroot')
    );
    $this->psr0Directory = $this->testPackage->getDirectory('lib');

    $this->mapper = m::mock('Webforge\Code\Generator\ClassFileMapper');
    $this->webforge->setClassFileMapper($this->mapper);

    $this->compiler = new Compiler($this->webforge->getClassWriter());
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

    $this->mapper->shouldReceive('getFile')->with($changedFQN)->andReturn($file);

    return $changedFQN;
  }

  protected function json($string) {
    return JSONConverter::create()->parse($string);
  }
}
