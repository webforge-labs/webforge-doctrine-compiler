<?php

namespace Webforge\Code\Generator;

abstract class ClassReaderBaseTest extends \Webforge\Code\Test\Base
{
    protected $classReader;

    protected $file;

    protected $php;

    public function setUp()
    {
        $this->classReader = new ClassReader();

        $this->file = $this->getMock('Webforge\Common\System\File', array('getContents'), array('ClassFile.php'));
    }

    protected function inClass($php)
    {
        $this->php = '<?php
class Point {
  ' . $php . '
}
?>';
        return $this->php;
    }

    protected function read()
    {
        if (isset($this->php)) {
            $this->expectFileHasContents($this->php);
        }

        $gClass = $this->classReader->read($this->file);

        $this->assertInstanceOf('Webforge\Code\Generator\GClass', $gClass, 'Reader does not return a GClass');

        return $gClass;
    }

    protected function assertThatReadGClass()
    {
        return $this->assertThatGClass($this->read());
    }

    protected function expectFileHasContents($contents, $times = null)
    {
        $this->file->expects($times ?: $this->once())->method('getContents')
               ->will($this->returnValue($contents));
    }
}
