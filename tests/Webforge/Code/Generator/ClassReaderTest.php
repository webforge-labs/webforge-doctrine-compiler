<?php

namespace Webforge\Code\Generator;

class ClassReaderTest extends ClassReaderBaseTest
{
    public function testReaderReadsAnEmptyClass()
    {
        $this->expectFileHasContents(<<<'PHP'
<?php
namespace ACME;

class Console {
  
}

PHP
        );

        $gClass = $this->read();

        $this->assertEquals('ACME\Console', $gClass->getFQN(), 'FQN of read class is wrong');
    }

    public function testPutsImportsForReadUses()
    {
        $this->expectFileHasContents(<<<'PHP'
<?php
namespace ACME;

use Webforge\Common\System\File;
use Webforge\Common\StringUtil as S;

class Console {
  
}

PHP
        );

        $gClass = $this->read();

        $this->assertCount(2, $imports = $gClass->getImports());
        $this->assertTrue($imports->have(new GClass('Webforge\Common\System\File')), 'imports do not have Webforge\Common\System\File');
        $this->assertTrue($imports->have('S'), 'imports do not have S as Alias. Parsed are: ' . implode(',', array_keys($imports->toArray())));
        $this->assertEquals('Webforge\Common\StringUtil', $imports->get('S')->getFQN());
    }

    public function testSetsParentInClassWhenClassExtendsSomething()
    {
        $this->expectFileHasContents(<<<'PHP'
<?php
namespace ACME;

class Console extends \Webforge\Console\Application {
  
}

PHP
        );

        $gClass = $this->read();

        $this->assertThatGClass($gClass)->hasParent('Webforge\Console\Application');
    }

    public function testReadsTheDocBlockOfClass()
    {
        $this->expectFileHasContents(<<<'PHP'
<?php
namespace ACME;

/**
 * The docblock
 */
class Console extends \Webforge\Console\Application {
  
}

PHP
        );

        $gClass = $this->read();

        $this->assertThatGClass($gClass)->hasDocBlock();
        $this->assertContains('The docblock', $gClass->getDocBlock()->toString());
    }

    public function testReadsTheClassAsAbstract()
    {
        $this->expectFileHasContents(<<<'PHP'
<?php

abstract class AbstractConsole {}

PHP
        );

        $this->assertThatGClass($this->read())->isAbstract();
    }

    public function testReadIntoReturnsTheSameClassFromArgument()
    {
        $php = $this->inClass('public function export() {}');
        $this->expectFileHasContents($php);

        $gClass = new GClass('does not matter');
        $rGClass = $this->classReader->readInto($this->file, $gClass);

        $this->assertSame($rGClass, $gClass);
        $this->assertThatGClass($gClass)->hasMethod('export');
    }


    public function testClassReaderThrowsRuntimeExIfPHPIsMalformed()
    {
        $this->php = '<?php a parse error ?>';

        $this->setExpectedException('RuntimeException');
        $this->read();
    }
}
