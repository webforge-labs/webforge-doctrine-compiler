<?php

namespace Webforge\Code\Generator;

use Webforge\Types\IntegerType;
use Webforge\Types\StringType;
use Webforge\Types\Type;

/**
 *
 */
class ClassWriterPHPTest extends \Webforge\Code\Test\Base
{
    /**
     * @var ClassWriter
     */
    protected $classWriter;

    /**
     * @var GClass
     */
    protected $gClass;

    public function setUp()
    {
        $this->classWriter = new ClassWriter();
        $this->gClass = new GClass('Blank');
    }

    public function testWriteGClass_ContainsDocBlock()
    {
        $gClass = new GClass('WithDocBlock');
        $gClass->createDocBlock('The comment');

        $phpCode =
        <<<'PHP'
/**
 * The comment
 */
class WithDocBlock
{
}
PHP;

        $this->assertSameStrings($phpCode, $this->classWriter->writeGClass($gClass, $namespace = null, "\n"));
    }

    public function testWriteGClass_ExtendsIsWrittenAsClassNameWhenInSameCONTEXTNamespace()
    {
        $gClass = GClass::create('ACME\Types\Type')->setParent(new GClass('ACME\Types\BaseType'));

        $phpCode =
        <<<'PHP'
class Type extends BaseType
{
}
PHP;

        $this->assertSameStrings($phpCode, $this->classWriter->writeGClass($gClass, $namespace = 'ACME\Types', "\n"));
    }

    public function testWriteGClass_ExtendsIsWrittenAsFullIfNotSameCONTEXTNamespace()
    {
        $gClass = GClass::create('ACME\Console')->setParent(new GClass('Webforge\System\Console'));

        $phpCode =
        <<<'PHP'
class Console extends \Webforge\System\Console
{
}
PHP;

        $this->assertSameStrings($phpCode, $this->classWriter->writeGClass($gClass, $namespace = 'ACME', "\n"));
    }

    public function testGClassHasModifiers()
    {
        $gClass = GClass::create('ACME\Console')->setAbstract(true);

        $phpCode =
        <<<'PHP'
abstract class Console
{
}
PHP;
        $this->assertSameStrings($phpCode, $this->classWriter->writeGClass($gClass, $namespace = 'ACME'));
    }

    public function testWritesGMethodWithParameters()
    {
        $method = GMethod::create(
            'someAction',
            array(
                GParameter::create('xValue', new GClass('PointValue')),
                GParameter::create('yValue', new GClass('PointValue')),
                GParameter::create('info', Type::create('Array'))
                    ->setDefault(array('x','y'))
            )
        );
        $phpCode = <<<'PHP'
public function someAction(PointValue $xValue, PointValue $yValue, Array $info = array('x','y'))
{
}
PHP;

        $this->assertSameStrings($phpCode, $this->classWriter->writeMethod($method));
    }

    public function testWritesGMethodBodyNearlyCorrect()
    {
        $method = GMethod::create(
            '__construct',
            array(),
            GFunctionBody::create(array(
                sprintf("parent::__construct('%s');\n", 'TheName')
            ))
        );

        $phpCode = <<<'PHP'
public function __construct()
{
    parent::__construct('TheName');
}
PHP;

        $this->assertSameStrings($phpCode, $this->classWriter->writeMethod($method));
    }

    public function testWritesParameterHintWithoutFQNWhenInNamespaceContext()
    {
        $param = GParameter::create('xValue', new GClass('Webforge\Geometric\PointValue'));

        $phpCode = 'PointValue $xValue';

        $this->assertEquals($phpCode, $this->classWriter->writeParameter($param, 'Webforge\Geometric'));
    }

    public function testWritesParameterHintWithoutFQNWhenHintWasImportet()
    {
        $param = GParameter::create('xValue', $point = new GClass('Webforge\Geometric\PointValue'));

        $this->classWriter->addImport($point);

        $phpCode = 'PointValue $xValue';

        $this->assertEquals($phpCode, $this->classWriter->writeParameter($param, 'Webforge\Other\Namesp'));
    }
    public function testWritesParameterHintWithoutFQNWhenHintWasInGClass()
    {
        $param = GParameter::create('yValue', $point = new GClass('Webforge\Geometric\PointValue'));

        $gClass = new GClass('WithImport');
        $gClass->createMethod('someActionWithY', array($param));

        $phpCode = 'someActionWithY(PointValue $yValue)';

        $this->assertContains($phpCode, $this->classWriter->writeGClassFile($gClass, 'Webforge\Other\Namesp'));
    }

    public function testWritesInterfaces()
    {
        $if = GInterface::create('ACME\Exportable');

        $phpCode =
        <<<'PHP'
interface Exportable
{
}
PHP;
        $this->assertSameStrings($phpCode, $this->classWriter->writeGClass($if, $namespace = 'ACME'));
    }

    public function testWritesInterfaceMethodsAsMethodWithoutBody()
    {
        $if = GInterface::create('ACME\Exportable');
        $if->createMethod('export');

        $phpCode =
        <<<'PHP'
interface Exportable
{
    public function export();
}
PHP;

        $this->assertSameStrings($phpCode, $this->classWriter->writeGClass($if, $namespace = 'ACME'));
    }

    public function testPropertyWithDefaultValue()
    {
        $this->gClass->addProperty(GProperty::create('defaultNamespace', 'String', 'Psc\CMS\Controllers'));

        $phpCode = "    protected \$defaultNamespace = 'Psc\\\\CMS\\\\Controllers';";

        $this->assertInnerCodeEquals($phpCode);
    }

    public function testPropertyWithoutDefaultValue()
    {
        $this->gClass->addProperty(GProperty::create('defaultNamespace', 'String'));

        $phpCode = "    protected \$defaultNamespace;";

        $this->assertInnerCodeEquals($phpCode);
    }

    public function testFullClass()
    {
        $gClass = GClass::create('ACME\CompiledEntity')->setAbstract(true);

        $method = GMethod::create(
            '__construct',
            array(),
            GFunctionBody::create(array(
                sprintf("parent::__construct('%s');\n", 'TheName'),
                '$this->other = \'def\';'
            ))
        );
        $gClass->addMethod($method);

        //$gClass->addConstant(GConstant::create('SET1', null, 8));

        $gClass->addProperty(
            GProperty::create('something', new StringType(), 'default')
                ->setDocBlock(new DocBlock('@var string'))
        );

        $gClass->addProperty(
            GProperty::create('created', new GClass('DateTimeInterface'))
                ->setDocBlock(new DocBlock('@var \DateTimeInterface'))
        );

        $method = GMethod::create(
            'noop',
            array(
            ),
            GFunctionBody::create([])
        );
        $method->setReturnTypeHint('void');

        $gClass->addMethod($method);

        $method = GMethod::create(
            'setCreated',
            array(
                GParameter::create('time', new GClass('DateTimeInterface'))
            ),
            GFunctionBody::create([
                '$this->created = $time;'
            ])
        );
        $gClass->addMethod($method);

        $phpCode = <<<'PHP'
abstract class CompiledEntity
{
    /**
     * @var string
     */
    protected $something = 'default';

    /**
     * @var \DateTimeInterface
     */
    protected $created;

    public function __construct()
    {
        parent::__construct('TheName');
        $this->other = 'def';
    }

    public function noop(): void
    {
    }

    public function setCreated(\DateTimeInterface $time)
    {
        $this->created = $time;
    }
}
PHP;

        $this->assertSameStrings(
            $phpCode,
            $this->classWriter->writeGClass($gClass, 'ACME')
        );
    }

    protected function assertInnerCodeEquals($innerPhpCode)
    {
        $phpCode =
        <<<'PHP'
class Blank
{
%s
}
PHP;

        $this->assertSameStrings(
            sprintf($phpCode, $innerPhpCode),
            $this->classWriter->writeGClass($this->gClass, $namespace = 'ACME')
        );
    }

    protected function assertSameStrings(string $expected, string $actual): void
    {
        $debugEol = "-n-";
        //$debugEol = '';
        $this->assertEquals(
            $expected,
            $actual,
            sprintf(
                "<<<expected>>>\n%s\n\n<<<actual>>>\n%s",
                strtr($expected, ["\n" => $debugEol . "\n"]),
                strtr($actual, ["\n" => $debugEol . "\n"])
            )
        );
    }
}
