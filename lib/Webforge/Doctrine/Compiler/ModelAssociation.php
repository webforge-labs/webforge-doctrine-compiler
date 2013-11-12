<?php

namespace Webforge\Doctrine\Compiler;

/**
 * An Association between to entities through their (two) properties
 * 
 * The $entity has a reference in $property to $referencedEntity (for $referencedProperty)
 */
class ModelAssociation {

  public $entity;
  public $property;

  public $referencedEntity;
  public $referencedProperty;

  /**
   * @var string
   */
  public $type;

  protected $owning;

  public function __construct($type, GeneratedEntity $entity, GeneratedProperty $property, GeneratedEntity $referencedEntity, GeneratedProperty $referencedProperty = NULL) {
    $this->type = $type;
    $this->entity = $entity;
    $this->property = $property;
    $this->referencedEntity = $referencedEntity;
    $this->referencedProperty = $referencedProperty;
    $this->owning = FALSE;
  }

  public function shouldUpdateOtherSide() {
    if ($this->type === 'ManyToOne') {
      return TRUE;
    }

    if ($this->type === 'ManyToMany' && $this->owning) {
      return TRUE;
    }

    if ($this->type === 'OneToOne' && $this->owning) {
      return TRUE;
    }

    return FALSE;
  }

  public function getSlug() {
    return sprintf("%s::%s => %s::%s", $this->entity->getName(), $this->property->getName(), $this->referencedEntity->getName(), $this->referencedProperty->getName());
  }

  public function getUniqueSlug() {
    $format = '%s::%s <=> %s::%s';

    if ($this->owning) {
      return sprintf($format, $this->entity->getName(), $this->property->getName(), $this->referencedEntity->getName(), $this->referencedProperty->getName());
    } else {
      return sprintf($format, $this->referencedEntity->getName(), $this->referencedProperty->getName(), $this->entity->getName(), $this->property->getName());
    }
  }

  public function setOwning($bool) {
    $this->owning = $bool;
  }

  public function isOwning() {
    return $this->owning;
  }

  /**
   * @return GeneratedProperty
   */
  public function getPropertyFor(GeneratedEntity $entity) {
    return $this->entity->equals($entity) ? $this->property : $this->referencedProperty;
  }
}