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
    if ($this->isUnidirectional()) {
      return FALSE;
    }

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

  public function isUnidirectional() {
    return !isset($this->referencedProperty);
  }

  public function getUniqueSlug() {
    if ($this->isUnidirectional()) {
      // i think we're always the owning side for unidirectional associations
      $format = '%s::%s <=> %s';

      return sprintf($format, $this->entity->getName(), $this->property->getName(), $this->referencedEntity->getName());
    } else {
      $format = '%s::%s <=> %s::%s';

      if ($this->owning) {
        return sprintf($format, $this->entity->getName(), $this->property->getName(), $this->referencedEntity->getName(), $this->referencedProperty->getName());
      } else {
        return sprintf($format, $this->referencedEntity->getName(), $this->referencedProperty->getName(), $this->entity->getName(), $this->property->getName());
      }
    } 
  }

  public function setOwning($bool) {
    $this->owning = $bool;
  }

  public function isOwning() {
    return $this->owning;
  }

  public function isOneToMany() {
    return $this->type === 'OneToMany';
  }

  public function isManyToOne() {
    return $this->type === 'ManyToOne';
  }

  public function isManyToMany() {
    return $this->type === 'ManyToMany';
  }

  public function isOneToOne() {
    return $this->type === 'OneToOne';
  }
}
