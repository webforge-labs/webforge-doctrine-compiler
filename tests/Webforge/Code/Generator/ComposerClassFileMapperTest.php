<?php

namespace Webforge\Code\Generator;

class ComposerClassFileMapperTest extends \Webforge\Code\Test\Base
{
    public function setUp()
    {
        $this->chainClass = __NAMESPACE__ . '\\ComposerClassFileMapper';
        parent::setUp();

        $this->autoLoader = $this->frameworkHelper->getBootContainer()->getAutoLoader();
        $this->composerMapper = new ComposerClassFileMapper($this->autoLoader);
    }

    public function testFindsAClassLoadingFile()
    {
        $file = $this->composerMapper->getFile(__CLASS__);

        $this->assertEquals(
            __FILE__,
            (string) realpath($file)
        );
    }

    public function testDoesNotFindNonseClasses()
    {
        $this->setExpectedException('Webforge\Code\ClassFileNotFoundException');

        $this->composerMapper->getFile('nonsensclassthatdoesnotexist');
    }

    public function testDoesNotFindEmptyFQNs()
    {
        $this->setExpectedException('InvalidArgumentException');

        $this->composerMapper->getFile('\\');
    }
}
