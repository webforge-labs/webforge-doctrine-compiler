<?php

namespace Webforge\Code\Generator;

use Webforge\Code\Test\Base;
use Webforge\Common\System\File;

class ClassElevatorTest extends Base
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ClassReader
     */
    protected $classReader;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ClassFileMapper
     */
    protected $classFileMapper;

    public function setUp()
    {
        $this->classReader = $this->getMockBuilder(ClassReader::class)->disableArgumentCloning()->getMock();

        $this->classFileMapper = $this->getMockBuilder(ClassFileMapper::class)->disableArgumentCloning()->getMock();

        $this->elevator = new ClassElevator(
            $this->classFileMapper,
            $this->classReader
        );

        $this->gClass = new GClass(get_class($this));
    }

    public function testThatElevatorReadsTheClassFromSource()
    {
        $this->classFileMapper->expects($this->once())->method('getFile')->will($this->returnValue(new File('none')));

        $this->classReader->expects($this->once())->method('readInto')
            ->will($this->returnValue($this->gClass));

        $this->assertNotSame($this->gClass, $this->elevator->getGClass(get_class($this)));
    }

    public function testParentElevation()
    {
        $this->classFileMapper->expects($this->once())->method('getFile')->will($this->returnValue(new File('none')));

        $child = new GClass('Webforge\Geometric\Point');
        $parent = new GClass('Webforge\Geometric\Base');
        $child->setParent($parent);

        $this->classReader->expects($this->once())->method('readInto')
            ->with($this->isInstanceOf('Webforge\Common\System\File'), $this->identicalTo($parent))
            ->will($this->returnValue($parent));

        $child = $this->elevator->elevateParent($child);

        $this->assertSame($parent, $child->getParent());
    }

    public function testInterfaceElevation()
    {
        $this->classFileMapper->expects($this->once())->method('getFile')->will($this->returnValue(new File('none')));

        $gClass = new GClass('Webforge\Geometric\Point');
        $exportable = new GClass('Webforge\Common\Exportable');
        $gClass->addInterface($exportable);

        $this->classReader->expects($this->once())->method('readInto')
            ->will(
                $this->returnCallback(
                    function ($file, $exportable) {
                        $exportable->createMethod('export');
                    }
                )
            );

        $gClass = $this->elevator->elevateInterfaces($gClass);

        $this->assertSame($exportable, $gClass->getInterface(0));
    }
}
