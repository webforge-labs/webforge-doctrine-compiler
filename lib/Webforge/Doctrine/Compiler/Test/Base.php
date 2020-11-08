<?php

namespace Webforge\Doctrine\Compiler\Test;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\Mapping\MappingException;
use Mockery as m;
use org\bovigo\vfs\vfsStream;
use RuntimeException;
use Webforge\Code\Generator\ClassElevator;
use Webforge\Code\Generator\ClassReader;
use Webforge\Code\Generator\GClass;
use Webforge\Common\ClassInterface;
use Webforge\Common\JS\JSONConverter;
use Webforge\Common\Preg;
use Webforge\Common\System\Dir;
use Webforge\Doctrine\Annotations\Writer as AnnotationsWriter;
use Webforge\Doctrine\Compiler\Compiler;
use Webforge\Doctrine\Compiler\EntityGenerator;
use Webforge\Doctrine\Compiler\EntityMappingGenerator;
use Webforge\Doctrine\Compiler\Extensions;
use Webforge\Doctrine\Compiler\GClassBroker;
use Webforge\Doctrine\Compiler\Inflector;
use Webforge\Doctrine\Compiler\ModelValidator;
use Webforge\Doctrine\Test\SchemaTestCase;

class Base extends SchemaTestCase

{
    protected $webforge;
    protected $compiler;
    protected $entityGenerator;
    protected $psr0Directory;
    protected $testPackage;

    public static $schemaCreated = true;

    public static $package;


    public function setUp()
    {
        $this->virtualPackageDirectory = $this->getVirtualDirectory('packageroot');
        parent::setUp();

        // enable JMS annotations per auto_loading
        AnnotationRegistry::registerLoader('class_exists');

        $this->webforge = $this->frameworkHelper->getWebforge();

        $this->setUpPackage();


        $this->compiler = new Compiler(
            $this->webforge->getClassWriter(),
            $this->entityGenerator = new EntityGenerator(
                $inflector = new Inflector(),
                new EntityMappingGenerator(
                    $writer = new AnnotationsWriter(),
                    array(new Extensions\Serializer($writer))
                ),
                new GClassBroker($this->classElevator)
            ),
            new ModelValidator()
        );
    }

    protected function setUpPackage()
    {
        // fake a local package in the virtual dir
        $this->blogPackage = $this->webforge->getPackageRegistry()->addComposerPackageFromDirectory(
            $this->virtualPackageDirectory
        );
        $this->psr0Directory = $this->blogPackage->getDirectory('lib');

        // inject classmapper (see unique file hack)
        $this->mapper = m::mock('Webforge\Code\Generator\ClassFileMapper');
        $this->classElevator = new ClassElevator($this->mapper, new ClassReader());
    }

    protected function initEntitiesPaths()
    {
        $this->entitiesPaths = array((string)$this->virtualPackageDirectory->sub('lib/ACME/Blog/Entities')->create());
        return $this->entitiesPaths;
    }

    protected function getVirtualDirectory($name)
    {
        $dir = vfsStream::setup($name);

        vfsStream::copyFromFileSystem((string)$this->getTestDirectory('acme-blog/'), $dir, 1024 * 8);

        return new Dir(vfsStream::url($name) . '/');
    }

    protected function assertDoctrineMetadata($entityClass)
    {
        $metadataFactory = $this->em->getMetadataFactory();

        if ($entityClass instanceof ClassInterface) {
            $entityClass = $entityClass->getFQN();
        }

        try {
            $info = $metadataFactory->getMetadataFor($entityClass);
        } catch (MappingException $e) {
            $errorFile = $this->webforge->getClassFileMapper()->getFile($entityClass);
            $e = new RuntimeException(
                "Doctrine cannot read the file-contents:\n" . $errorFile->getContents(
                ) . "\nError was: " . $e->getMessage()
            );
            throw $e;
        }

        $this->assertEquals(
            $entityClass,
            $info->name,
            'The name is expected to be other than the read one from doctrine - thats an testing error'
        );

        return $info;
    }

    protected function changeUniqueClassName($file, &$foundClassName)
    {
        // quick and dirty
        $newClassName = null;
        $file->writeContents(
            Preg::replace_callback(
                $contents = $file->getContents(),
                '/^(.*?)class\s+(.*?)\s+(.*)$/im',
                function ($match) use (&$newClassName, &$foundClassName) {
                    $foundClassName = $match[2];
                    $newClassName = 'A' . uniqid() . $foundClassName;

                    return sprintf('%sclass %s %s', $match[1], $newClassName, $match[3]);
                }
            )
        );

        $namespace = Preg::qmatch($contents, '/^namespace\s+(.*);\s+$/mi');
        $changedFQN = $namespace . '\\' . $newClassName;

        // duplicate for doctrine
        $dupl = clone $file;
        $dupl->setName($newClassName);
        $file->copy($dupl);

        $this->doctrineFile = $dupl;
        require $dupl;

        $this->mapper->shouldReceive('getFile')->with($changedFQN)->andReturn($file);

        return $changedFQN;
    }

    protected function json($string)
    {
        return JSONConverter::create()->parse($string);
    }

    protected function elevateFull($fqn)
    {
        if ($fqn instanceof ClassInterface) {
            $fqn = $fqn->getFQN();
        }

        $elevator = $this->webforge->getClassElevator();

        $gClass = $elevator->getGClass($fqn);
        $elevator->elevateParent($gClass);

        return $gClass;
    }

    protected function getCompiledClass($entityClass)
    {
        $entityClass = new GClass($entityClass);
        $parentClass = new GClass($entityClass->getFQN());
        $parentClass->setName('Compiled' . $entityClass->getName());
        return $parentClass;
    }
}
