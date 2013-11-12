<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Common\System\Dir;
use Webforge\Common\ClassUtil;
use Webforge\Code\Generator\ClassWriter;
use Webforge\Code\Generator\DocBlock;
use Webforge\Code\Generator\GClass;
use Webforge\Code\Generator\ClassElevator;

class Compiler implements GClassBroker {

  protected $flags;
  protected $model;
  protected $validator;
  protected $classElevator;

  protected $dir;
  protected $classWriter;

  protected $generatedEntities;

  const PLAIN_ENTITIES = 0x000001;
  const COMPILED_ENTITIES = 0x000002;
  const RECOMPILE = 0x000004;

  public function __construct(ClassWriter $classWriter, EntityGenerator $entityGenerator, ModelValidator $validator, ClassElevator $classElevator) {
    $this->classWriter = $classWriter;
    $this->validator = $validator;
    $this->entityGenerator = $entityGenerator;
    $this->classElevator = $classElevator;
  }

  public function compileModel(stdClass $model, Dir $target, $flags) {
    $this->flags = $flags;
    $this->dir = $target;
    $this->model = $this->validator->validateModel($model);

    $this->entityGenerator->generate($this->model, $this);

    foreach ($this->entityGenerator->getEntities() as $entity) {
      list($entityFile, $compiledEntityFile) = $this->compileEntity($entity);
      $this->generatedEntities[$entity->getFQN()] = $entity;
      //print "compiled entity ".$entity->name.' to '.$entityFile."\n";
    }
  }

  protected function compileEntity(GeneratedEntity $entity) {
    $compiledEntityFile = $compiledClass = NULL;

    if ($this->flags & self::COMPILED_ENTITIES) {
      // we split up the gclass into Compiled$entityName and $entityName class
      // the $entityName class needs the docblock from the "real" class because this is what doctrine sees
      $entityClass = $entity->gClass;

      $compiledClass = clone $entityClass;
      $compiledClass->setName('Compiled'.$entityClass->getName());
      $compiledClass->setAbstract(TRUE);
      $compiledClass->setDocBlock(new DocBlock('Compiled Entity for '.$entityClass->getFQN()."\n\nTo change table name or entity repository edit the ".$entityClass->getFQN().' class.'));

      // entity extends Generation-Gap Entity
      $entityClass->setParent($compiledClass);

      foreach ($entityClass->getMethods() as $method) {
        $entityClass->removeMethod($method);
      }

      foreach ($entityClass->getProperties() as $property) {
        $entityClass->removeProperty($property);
      }

      // write both
      $entityFile = $this->write($entityClass);
      $compiledEntityFile = $this->write($compiledClass, $this->flags & self::COMPILED_ENTITIES ? ClassWriter::OVERWRITE : FALSE);

    } else {
      $entityFile = $this->write($entity->gClass);
    }

    return array($entityFile, $compiledEntityFile);
  }

  protected function write(GClass $gClass) {
    $entityFile = $this->mapToFile($gClass->getFQN());
    $entityFile->getDirectory()->create();

    $this->classWriter->write($gClass, $entityFile);

    return $entityFile;
  }

  /**
   * @return Webforge\Common\System\File
   */
  protected function mapToFile($fqn) {
    $url = str_replace('\\', '/', $fqn).'.php';

    return $this->dir->getFile($url);
  }

  public function getElevated($fqn, $debugEntity) {
    if (array_key_exists($fqn, $this->generatedEntities)) {
      return $this->generatedEntities[$fqn];
    }

    $searchClass = new GClass($fqn);

    // @FIXME: this will fail for sub-namespaces (getName is not the subnamespace + name)
    if ($searchClass->isInNamespace($this->model->getNamespace()) && $this->model->hasEntity($searchClass->getName())) {
      throw new ModelCompilerException(
        sprintf (
          'You tried to reference the entity "%1$s" that is in the model but is not generated yet. Cannot generate "%2$s" before generating "%1$s". Change the order in the model.',
          $fqn, $debugEntity
        )
      );
    }

    // its another class that will be elevated
    $gClass = $this->classElevator->getGClass($fqn);
    $this->classElevator->elevateParent($gClass);
    $this->classElevator->elevateInterfaces($gClass);

    return $gClass;
  }
}
