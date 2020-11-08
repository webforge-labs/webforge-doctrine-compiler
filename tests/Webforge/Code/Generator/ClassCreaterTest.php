<?php

namespace Webforge\Code\Generator;

use Webforge\Common\System\File;

class ClassCreaterTest extends \Webforge\Code\Test\Base
{
    protected $file;
    protected $classCreater, $classFileMapper, $classWriter;

    public function setUp()
    {
        $this->classFileMapper = $this->getMockForAbstractClass('ClassFileMapper');
        $this->classElevator = $this->getMock('ClassElevator', array(), array(), '', false);

      //$this->classWriter = $this->getMock('ClassWriter', array('write'));
        $this->classWriter = new ClassWriter();
        $this->classCreater = new ClassCreater($this->classFileMapper, $this->classWriter, $this->classElevator);
    }

    public function testClassCreaterWritesAnEmptyClassPerDefault()
    {
        $this->expectClassMapping(
            'Webforge\Code\Generator\Fixtures\MyClass1',
            $this->once()
        );

        $file = $this->classCreater->create(
            new GClass('Webforge\Code\Generator\Fixtures\MyClass1')
        );

        $php =
        <<<'PHP'
<?php

namespace Webforge\Code\Generator\Fixtures;

class MyClass1 {
  
}

PHP;

        $this->assertThatFileWasWritten($file);
        $this->assertThatFileCodeEquals($file, $php);
    }

    public function testClassCreaterCreatesStubsForInterfacesAcceptance()
    {
        $this->expectClassMapping(
            'Webforge\Code\Generator\Fixtures\MyGPSLocateableClass'
        );

        $this->classElevator->expects($this->once())->method('elevateInterfaces')
                        ->will($this->returnCallback(function ($gClass) {
                            return $gClass->getInterface(0)
                            ->createMethod('getLongitude')->getGClass()
                            ->createMethod('getLatitude')->getGClass()
                            ;
                        }));

        $this->classCreater->setClassElevator(
            $this->classElevator
        );


        $file = $this->classCreater->create(
            GClass::create('Webforge\Code\Generator\Fixtures\MyGPSLocateableClass')
            ->addInterface(new GInterface('Webforge\TestData\PHPClasses\GPSLocateable'))
        );

        $php =
        <<<'PHP'
<?php

namespace Webforge\Code\Generator\Fixtures;

class MyGPSLocateableClass implements \Webforge\TestData\PHPClasses\GPSLocateable {

  public function getLongitude() {
    
  }
  
  public function getLatitude() {
    
  }
}

PHP;

        $this->assertThatFileWasWritten($file);
        $this->assertThatFileCodeEquals($file, $php);
    }

    protected function assertThatFileWasWritten(File $returnedFile)
    {
        $this->assertEquals((string) $this->file, (string) $returnedFile, 'returnedFile and this->file mismatch!');
        $this->assertFileExists($returnedFile); // with our temporary file mock, this passes all the time, when its called
    }

    protected function assertThatFileCodeEquals(File $file, $expectedPHPCode)
    {
        $this->assertCodeEquals($expectedPHPCode, $actualPHP = $file->getContents());
    }

    protected function expectClassMapping($fqn, $constraint = null)
    {
        $this->classFileMapper
        ->expects($constraint ?: $this->once())->method('getFile')
        ->with(is_string($fqn) ? $this->equalTo($fqn) : $fqn)
        ->will($this->returnCallback(array($this, 'initTemporaryFile')));
    }

    protected function expectParentElevation($constraint = null)
    {
        $this->classFileMapper
        ->expects($constraint ?: $this->once())->method('elevateParent')
        ->will($this->returnCallback(new GClass()));
    }

    protected function expectClassisWritten()
    {
        $this->classWriter
        ->expects($constraint ?: $this->once())->method('write')
        ->with($this->instanceOf(__NAMESPACE__ . '\\GClass'), $this->instanceOf('Webforge\Common\System\File'))
        ->will($this->returnSelf());
    }

    public function initTemporaryFile()
    {
        if (!isset($this->file)) {
            $this->file = File::createTemporary(); // create the file
            $this->file->delete();                 // delete it, so that we simulate the behaviour of file exists correctly
        }
        return $this->file;
    }

    public function tearDown()
    {
        if (isset($this->file)) {
            $this->file->delete();
        }

        parent::tearDown();
    }
}
