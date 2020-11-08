<?php

namespace Webforge\Code\Generator;

class ClassWriterTest extends \Webforge\Code\Test\Base
{
    /**
     * @var ClassWriter
     */
    protected $classWriter;

    /**
     * @var GClass
     */
    protected $classWithImports;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Webforge\Common\System\File
     */
    protected $file;

    /**
     * @var GClass
     */
    protected $classWithoutImports;

    public function setUp()
    {
        $this->classWriter = new ClassWriter();

        $this->file = $this->getMock(\Webforge\Common\System\File::class, array('writeContents','exists'), array('tmp'));
        $this->classWithImports = new GClass('Webforge\Code\Generator\Fixtures\MyGPSLocateableClass');
        $this->classWithImports->addImport(new GClass('Other\UsedClass'));
        $this->classWithoutImports = new GClass('Webforge\Code\Generator\Fixtures\MyEmptyClass');
    }

    public function testThatNamespaceAndTagsAreWritten()
    {
        $this->expectThatWrittenCode(
            $this->logicalAnd(
                $this->stringContains('namespace Webforge\Code\Generator\Fixtures;'),
                $this->stringStartsWith('<?php'),
                $this->stringEndsWith("}\n"),
                $this->logicalNot($this->stringEndsWith("}\n\n"))
            )
        );

        $this->classWriter->write($this->classWithoutImports, $this->file);
    }

    public function testGClassOwnImportsAreWrittenToFile()
    {
      // the extraction from GClass is tested in import, we use little acceptance here, to ensure classWriter calls merge
        $this->expectThatWrittenCode($this->stringContains('use Other\UsedClass;'));

        $this->classWriter->write($this->classWithImports, $this->file);
    }

    public function testIfGClassWithoutImportsIsWritten_NoUseIsInFile()
    {
        $this->expectThatWrittenCode($this->logicalNot($this->stringContains('use')));

        $this->classWriter->write($this->classWithoutImports, $this->file);
    }

    public function testGClassOwnImportsDontGetMergedWithTheClassWriterImports()
    {
        $this->classWriter->addImport(new GClass('Doctrine\ORM\Mapping', 'ORM'));

        $expectedImports = $this->classWriter->getImports()->toArray();

        $this->classWriter->write($this->classWithImports, $this->file);

        $this->assertEquals($expectedImports, $this->classWriter->getImports()->toArray());
    }

    public function testClassWriterDoesNotOverwriteExistingFiles()
    {
        $this->expectFileExists(true);

        $this->setExpectedException('Webforge\Code\Generator\ClassWritingException');

        $this->classWriter->write($this->classWithImports, $this->file);
    }

    public function testPropertiesCanHaveLiteralDefaultValuesThatGetWrittnByTheWritterLiterally()
    {
        $gProperty = new GProperty(
            'propWithDefault',
            \Webforge\Types\Type::create('Float'),
            '0.5'
        );
        $gProperty->interpretDefaultValueLiterally();

        $php = $this->classWriter->writeProperty($gProperty, 0);
        $this->assertContains('$propWithDefault = 0.5', $php);
    }

    public function testArgumentsCanHaveLiteralDefaultValuesThatGetWrittenByTheWriterLiterally()
    {
        $param = new GParameter(
            'argWithDefault',
            \Webforge\Types\Type::create('Integer'),
            '\\ACME\\Blog\\ParameterType::SOMETHING'
        );
        $param->interpretDefaultValueLiterally();

        $php = $this->classWriter->writeParameter($param, 'ACME\\Blog\\Entities');
        $this->assertContains('$argWithDefault = \\ACME\\Blog\\ParameterType::SOMETHING', $php);
    }

    public function testParametersWithTypeHintsWillGetNamespaceStrippedIfClassWasWImported()
    {
        $this->classWriter->addImport(new \Webforge\Common\PHPClass('Doctrine\\Common\\Collections\\Collection'));
        $param = new GParameter(
            'entities',
            new \Webforge\Types\PersistentCollectionType(new \Webforge\Common\PHPClass('ACME\Blog\Post'))
        );

        $php = $this->classWriter->writeParameter($param, 'ACME\\Blog\\Entities');
        $this->assertStringStartsWith('Collection $entities', $php);
    }

    protected function expectThatWrittenCode($constraint, $times = null)
    {
        $this->expectFileExists(false);
        $this->file->expects($times ?: $this->once())->method('writeContents')
              ->with($constraint)->will($this->returnValue(233));
    }

    protected function expectFileExists($bool = false, $times = null)
    {
        $this->file->expects($times ?: $this->once())->method('exists')
              ->will($this->returnValue($bool));
    }
}
