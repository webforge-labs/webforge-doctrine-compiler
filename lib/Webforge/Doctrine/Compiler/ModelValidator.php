<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Types\Type;
use Webforge\Types\TypeException;
use Webforge\Types\CollectionType;
use Webforge\Types\ObjectType;

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

    // snd pass: check names, check entity property references
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

      // check entity property references
      foreach ($entity->properties as $name=>$property) {
        $this->validateType($property, $entity, $this->model);
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

    if (!isset($entity->constructor)) {
      $entity->constructor = array();
    }

    $constructor = new stdClass;
    foreach ($entity->constructor as $key=>$value) {
      if (is_string($value)) {
        $propertyName = $value;
        if (!isset($entity->properties->$propertyName)) {
          throw new InvalidModelException(
            sprintf("Undefined property '%s' in the constructor from entity %s. Only property-names can be used", $propertyName, $entity->name)
          );
        }

        $constructor->$propertyName = (object) array('name'=>$propertyName);
      } // @TODO else: validate constructor parameter-object
    }
    $entity->constructor = $constructor;

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

    if (!isset($definition->nullable)) {
      $definition->nullable = FALSE;
    }

    return $definition;
  }

  protected function validateType(stdClass $property, stdClass $entity, Model $model) {
    $typeName = $property->type;
    if ($property->type === 'DefaultId') {
      $typeName = 'Id';
    }

    // single entity reference
    if ($model->hasEntity($typeName)) {
      $referenceEntityName = $typeName;
      $type = new EntityReference($this->model->getEntity($referenceEntityName));
    } else {

      try {
        $type = Type::create($typeName);
      } catch (TypeException $e) {
        throw new InvalidModelException(sprintf("The type: '%s' cannot be parsed for entity '%s'.", $typeName, $entity->fqn), 0, $e);
      }

      // collection entity reference
      if ($type instanceof CollectionType && $type->getType() instanceof ObjectType && $model->hasEntity($referenceEntityName = $type->getType()->getClass()->getName())) {
        $type = new EntityCollectionReference($model->getEntity($referenceEntityName));
      }
    }

    $property->type = $type;
  }
}
