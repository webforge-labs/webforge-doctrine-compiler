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

  protected $tableName;

  public function __construct($type, GeneratedEntity $entity, GeneratedProperty $property, GeneratedEntity $referencedEntity, GeneratedProperty $referencedProperty = NULL, $owning = FALSE) {
    $this->type = $type;
    $this->entity = $entity;
    $this->property = $property;
    $this->referencedEntity = $referencedEntity;
    $this->referencedProperty = $referencedProperty;
    $this->owning = $owning;

    if ($this->owning) {
      // later ones will override previous
      $tabProperties = array($this->referencedProperty, $this->property);
    } else {
      $tabProperties = array($this->property, $this->referencedProperty);
    }

    foreach ($tabProperties as $prop) {
      if ($prop && $prop->hasJoinTableName()) {
        $this->tableName = $prop->getJoinTableName();
      }
    }
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

  public function isSelfReferencing() {
    return $this->entity->equals($this->referencedEntity);
  }

  public function isEqual(ModelAssociation $other) {
    return $this->getUniqueSlug() === $other->getUniqueSlug();
  }

  public function getTableName() {
    if (isset($this->tableName)) {
      return $this->tableName;
    } else {
      $format = '%s2%s';

      if ($this->owning) {
        return sprintf($format, $this->entity->getTableName(), $this->referencedEntity->getTableName());
      } else {
        return sprintf($format, $this->referencedEntity->getTableName(), $this->entity->getTableName());
      }
    }
  }
}
