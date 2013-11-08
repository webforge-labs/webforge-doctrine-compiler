<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;

class ModelValidator {

  protected $model;

  public function validateModel(stdClass $model) {
    if (!isset($model->namespace) || empty($model->namespace)) {
      throw new InvalidModelException('The .namespace cannot be empty');
    }

    if (!isset($model->entities) || !is_array($model->entities)) {
      throw new InvalidModelException('The .entities have to be an array');
    }

    $entities = array();
    foreach ($model->entities as $key => $entity) {
      $entities[$key] = $this->validateEntity($entity, $key);
    }

    $this->model = new Model($model->namespace, $entities);

    // snd pass: check names
    foreach ($this->model->getEntities() as $entity) {
      if (isset($entity->extends)) {

        if (empty($entity->extends)) {
          throw new InvalidModelException('Entity in model with key "'.$key.'" has to have a non empty value in "extends"');
        }

        if (!$this->model->hasEntity($entity->extends)) {
          throw new InvalidModelException('Entity '.$entity->name.' extends an unknown entity "'.$entity->extends.'".');
        }

        $entity->extends = $this->model->getEntity($entity->extends);
      } else {
        $entity->extends = NULL;
      }
    }

    return $this->model;
  }

  protected function validateEntity($entity, $key) {
    if (!($entity instanceof stdClass)) {
      throw new InvalidModelException('Entity in model with key "'.$key.'" has to be an object');
    }

    if (!isset($entity->name) || empty($entity->name)) {
      throw new InvalidModelException('Entity in model with key "'.$key.'" has to have a non empty property name');
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
    if (is_string($definition)) {
      $definition = (object) array(
        'type'=>$definition
      );
    }

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
