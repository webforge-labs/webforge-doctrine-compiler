<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Common\System\Dir;
use Webforge\Common\ClassUtil;
use Webforge\Code\Generator\ClassWriter;

class Compiler {

  protected $flags;
  protected $model;

  protected $dir;
  protected $classWriter;

  const PLAIN_ENTITIES = 0x000001;
  const COMPILED_ENTITIES = 0x000002;

  public function __construct(ClassWriter $classWriter, EntityGenerator $entityGenerator) {
    $this->classWriter = $classWriter;
    $this->entityGenerator = $entityGenerator;
  }

  public function compileModel(stdClass $model, Dir $target, $flags) {
    $this->flags = $flags;
    $this->dir = $target;
    $this->model = $this->validateModel($model);

    foreach ($this->model->entities as $entity) {
      list($gClass, $entityFile) = $this->compileEntity($entity);
    }
  }

  protected function compileEntity(stdClass $entity) {
    $writeFQN = $this->getWriteFQN($entity);

    $gClass = $this->entityGenerator->generate($entity, $writeFQN);

    $entityFile = $this->mapToFile($writeFQN);
    $entityFile->getDirectory()->create();

    $this->classWriter->write($gClass, $entityFile);

    return array($gClass, $entityFile);
  }

  /**
   * @return Webforge\Common\System\File
   */
  protected function mapToFile($fqn) {
    $url = str_replace('\\', '/', $fqn).'.php';

    return $this->dir->getFile($url);
  }

  protected function getWriteFQN(stdClass $entity) {
    return ClassUtil::expandNamespace($entity->name, $this->model->namespace);
  }

  public function validateModel(stdClass $model) {
    if (!isset($model->namespace) || empty($model->namespace)) {
      throw new InvalidModelException('The .namespace cannot be empty');
    }

    if (!isset($model->entities) || !is_array($model->entities)) {
      throw new InvalidModelException('The .entities have to be an array');
    }

    foreach ($model->entities as $key => $entity) {
      $model->entities[$key] = $this->validateEntity($entity, $key);
    }

    return $model;
  }

  protected function validateEntity($entity, $key) {
    if (!($entity instanceof stdClass)) {
      throw new InvalidModelException('Entity in model with key "'.$key.'" has to be an object');
    }

    if (!isset($entity->name) || empty($entity->name)) {
      throw new InvalidModelException('Entity in model with key "'.$key.'" has to have a non empty property name');
    }

    if (isset($entity->members)) {
      $entity->properties = $entity->members;
    }

    if (!isset($entity->properties)) {
      $entity->properties = new stdClass;
    }

    foreach ($entity->properties as $name => $propertyDefinition) {
      $entity->properties->$name = $this->validateProperty($propertyDefinition, $name, $entity->name);
    }

    return $entity;
  }

  protected function validateProperty($definition, $name, $entityName) {
    if (!($definition instanceof stdClass)) {
      throw new InvalidModelException('Definition of the property with name "'.$name.'" in entity "'.$entityName.'" has to be an object');
    }

    if (!isset($definition->type)) {
      $definition->type = 'String';
    }

    if (!isset($entity->nullable)) {
      $definition->nullable = FALSE;
    }

    return $definition;
  }
}
