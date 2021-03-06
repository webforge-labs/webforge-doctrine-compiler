<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Code\Generator\ClassWriter;
use Webforge\Code\Generator\DocBlock;
use Webforge\Code\Generator\GClass;
use Webforge\Common\System\Dir;

class Compiler
{
    protected $flags;
    protected $model;
    protected $validator;
    protected $broker;

    protected $dir;

    /**
     * @var ClassWriter
     */
    protected $classWriter;

    protected $generatedEntities;

    public const PLAIN_ENTITIES = 0x000001;
    public const COMPILED_ENTITIES = 0x000002;
    public const RECOMPILE = 0x000004;
    public const EXPORT_MODEL = 0x000008;

    public function __construct(ClassWriter $classWriter, EntityGenerator $entityGenerator, ModelValidator $validator)
    {
        $this->classWriter = $classWriter;
        $this->validator = $validator;
        $this->entityGenerator = $entityGenerator;
    }

    public function compileModel(stdClass $model, Dir $target, $flags)
    {
        $this->flags = $flags;
        $this->dir = $target;
        $this->model = $this->validator->validateModel($model);

        $this->entityGenerator->generate($this->model);
        $this->generatedEntities = array();

        foreach ($this->entityGenerator->getEntities() as $entity) {
            list($entityFile, $compiledEntityFile) = $this->compileEntity($entity);
            $this->generatedEntities[$entity->getFQN()] = $entity;
            //print "compiled entity ".$entity->name.' to '.$entityFile."\n";
        }

        if ($flags & self::EXPORT_MODEL) {
            $exporter = new ModelExporter();
            return $exporter->exportModel($this->model, $this->entityGenerator);
        }
    }

    protected function compileEntity(GeneratedEntity $entity)
    {
        $compiledEntityFile = $compiledClass = null;

        if ($this->flags & self::COMPILED_ENTITIES) {
            // we split up the gclass into Compiled$entityName and $entityName class
            // the $entityName class needs the docblock from the "real" class because this is what doctrine sees
            $entityClass = clone $entity->gClass;

            $compiledClass = clone $entityClass;
            $compiledClass->setName('Compiled' . $entityClass->getName());
            $compiledClass->setAbstract(true);
            $compiledClass->setDocBlock(
                $docBlock = new DocBlock(
                    'Compiled Entity for ' . $entityClass->getFQN(
                    ) . "\n\nTo change table name or entity repository edit the " . $entityClass->getFQN(
                    ) . ' class.' . "\n" .
                    '@ORM\MappedSuperclass'
                )
            );

            // entity extends Generation-Gap Entity
            $entityClass->setParent($compiledClass);

            foreach ($entityClass->getMethods() as $method) {
                $entityClass->removeMethod($method);
            }

            foreach ($entityClass->getProperties() as $property) {
                $entityClass->removeProperty($property);
            }

            // write both
            $entityFile = $this->write($entityClass, 'entityFile');
            $compiledEntityFile = $this->write($compiledClass, 'compiledEntityFile');
        } else {
            $entityFile = $this->write($entity->gClass, 'entityFile');
        }

        return array($entityFile, $compiledEntityFile);
    }

    protected function write(GClass $gClass, $type)
    {
        $entityFile = $this->mapToFile($gClass->getFQN());

        if ($type === 'entityFile' && $entityFile->exists()) {
            // dont write. warn?
        } else {
            $entityFile->getDirectory()->create();

            $this->classWriter->write(
                $gClass,
                $entityFile,
                ($type === 'compiledEntityFile' && ($this->flags & self::COMPILED_ENTITIES))
                    ? ClassWriter::OVERWRITE : false
            );
        }

        return $entityFile;
    }

    /**
     * @return Webforge\Common\System\File
     */
    protected function mapToFile($fqn)
    {
        $url = str_replace('\\', '/', $fqn) . '.php';

        return $this->dir->getFile($url);
    }
}
