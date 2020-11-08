<?php

namespace Webforge\Code\Generator;

class ImportsTest extends \Webforge\Code\Test\Base
{
    protected $imports;
    protected $mapping, $helper;

    public function setUp()
    {
        parent::setup();
        $this->imports = new Imports(
            array(
            $this->mapping = GClass::create('Doctrine\ORM\Mapping'),
            'DoctrineHelper' => $this->helper = GClass::create('Psc\Doctrine\Helper')
            )
        );

        $this->logicException = 'LogicException';
    }

    public function testConstructWithElementsAddsElements()
    {
        $this->assertCount(2, $this->imports);

        $aliases = array();
        foreach ($this->imports as $alias => $import) {
            $aliases[] = $alias;
            $this->assertInstanceOf('Webforge\Code\Generator\GClass', $import, 'Alias ' . $alias . ' ist keine GClass');
        }

        $this->assertEquals(array('Mapping', 'DoctrineHelper'), $aliases, 'Aliases do not match');
    }

  /**
   * @expectedException Psc\Exception
   * @expectedExceptionMessage Alias: DoctrineHelper is already used by Class Psc\Doctrine\Helper
   */
    public function testAddingAnExistingsAliasIsNotAllowed()
    {
        $this->setExpectedException($this->logicException);
        $this->imports->add(GClass::create(get_class($this)), 'DoctrineHelper');
    }

    public function testAddingAnExistingsAliasIsNotAllowedAndIsCaseInsensitiv()
    {
        $this->setExpectedException($this->logicException);

        $this->imports->add(GClass::create(get_class($this)), 'doctrinehelper');
    }

    public function testAddingAnExistingAliasIsAllowedIfFQNIsResolvedToTheSameClass()
    {
        $this->imports->add(GClass::create($this->helper->getFQN()), 'DoctrineHelper');
    }

  /**
   * @depends testAddingAnExistingsAliasIsNotAllowed
   */
    public function testAddingAnExistingAliasWorks_IfItsRemovedBefore()
    {
        $this->imports->remove('DoctrineHelper');
        $this->imports->add(GClass::create(get_class($this)), 'DoctrineHelper');
    }

    public function testAddingAnEmptyGClassIsNotAllowed()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->imports->add(new GClass());
    }

    public function testGetAliasReturnsAlias_()
    {
        $this->assertEquals('Mapping', $this->imports->getAlias($this->mapping));
        $this->assertEquals('DoctrineHelper', $this->imports->getAlias($this->helper));
    }

    public function testRemoveByAliasRemovesTheImport()
    {
        $this->imports->remove('DoctrineHelper');
        $this->assertFalse($this->imports->have('DoctrineHelper'));
    }

    public function haveReturnsTrueForClassThatIsAliased()
    {
        $this->assertTrue($this->imports->have($this->helper));
    }

    public function testRemoveByGClassRemovesTheImport_too()
    {
        $this->imports->remove($gClass = GClass::create('Psc\Doctrine\Helper'));
        $this->assertFalse($this->imports->have($gClass), 'gClass as instance should be removed, but isnt');
        $this->assertFalse($this->imports->have('DoctrineHelper'), 'gClass DoctrineHelper as alias should be removed, but isnt');

        $this->imports->remove('Mapping', 'import cannot be removed with implicit alias');
        $this->assertFalse($this->imports->have('Mapping'));
    }

    public function testClonedImportsAreEqual()
    {
        $imports = clone $this->imports;
        $this->assertEquals(
            $imports->toArray(),
            $this->imports->toArray()
        );
    }

    public function testImportsCanBeMergedFromImportsFromGClass()
    {
        $classImports = new Imports(array('Webforge' => new GClass('Webforge\Doctrine\Annotations'),
                                      new GClass('stdClass')
                                      ));

        $gClass = $this->getMock('GClass', array('getImports', 'toArray'));
        $gClass->expects($this->once())->method('getImports')->will($this->returnValue($classImports));

        $this->imports->mergeFromClass($gClass);

        $this->assertArrayEquals(
            array('Mapping', 'DoctrineHelper', 'Webforge', 'stdClass'),
            array_keys($this->imports->toArray()),
            'imports are not correctly merged from gClass'
        );
    }


    public function testImportsToPHPWithAlias()
    {
        $imports = new Imports(
            array('CodeGenerator' => new GClass('Psc\Code\Generator'))
        );

        $this->assertCodeEquals(
            'use Psc\Code\Generator AS CodeGenerator;',
            $imports->php('Psc\Code\Generator')
        );
    }

    public function testImportsToPHPWithoutAlias()
    {
        $imports = new Imports(
            array(new GClass('Psc\Code\Generator'))
        );

        $this->assertCodeEquals(
            'use Psc\Code\Generator;',
            $imports->php('Psc\Code\Generator')
        );
    }
}
