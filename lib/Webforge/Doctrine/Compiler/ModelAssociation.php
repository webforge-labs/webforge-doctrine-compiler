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

  protected $orderBy;

  public function __construct(GeneratedEntity $entity, GeneratedProperty $property, GeneratedEntity $referencedEntity, GeneratedProperty $referencedProperty = NULL) {
    $this->entity = $entity;
    $this->property = $property;
    $this->referencedEntity = $referencedEntity;
    $this->owning = FALSE;

    $isSelfReferencing = $entity->equals($referencedEntity);    
    
    if ($isSelfReferencing) {
      // $referencedProperty is always defined, because we will at least find our self as "other" side
      $this->referencedProperty = $referencedProperty;

      if ($this->referencedProperty->getName() === $this->property->getName()) {
        // the property and referencedProperty are the same
        $this->owning = TRUE;

        if ($property->isEntityCollection()) {
          $this->type = 'ManyToMany';
        } else {
          $this->type = 'OneToOne';
        }
      } else {

        // the property is another property in this entity
        if ($property->isEntityCollection()) {
          if ($referencedProperty->isEntityCollection()) {
            $this->type = 'ManyToMany';
            $this->owning = isset($property->getDefinition()->isOwning) && $property->getDefinition()->isOwning; // this might produce a conflict that no side isOwning (check chis later)
          } else {
            $this->type = 'OneToMany';
            $this->owning = FALSE;
          }
        } elseif ($property->isEntity()) {
          if ($referencedProperty->isEntityCollection()) {
            $this->type = 'ManyToOne';
            $this->owning = TRUE;
          } else {
            $this->type = 'OneToOne';
            $this->owning = isset($property->getDefinition()->isOwning) && $property->getDefinition()->isOwning;
          }
        }
      }

    } elseif ($isUnidirectional = !isset($referencedProperty)) {
      $this->owning = TRUE;

      if ($property->isEntityCollection()) {
        $this->type = 'ManyToMany';
      } elseif ($property->isEntity() && $property->getRelationName() === 'OneToOne') {
        $this->type = 'OneToOne';
      } elseif ($property->isEntity()) {
        // this is a more sensible default then OneToOne because OneToOne is used less often than ManyToOne in that case
        $this->type = 'ManyToOne';
      }

    } else {
      $this->referencedProperty = $referencedProperty;

      if ($property->isEntityCollection()) {
        if ($referencedProperty->isEntityCollection()) {
          $this->type = 'ManyToMany';
          $this->owning = isset($property->getDefinition()->isOwning) && $property->getDefinition()->isOwning; // this might produce a conflict that no side isOwning (check chis later)
        } else {
          $this->type = 'OneToMany';
          $this->owning = FALSE;
        }
      } elseif ($property->isEntity()) {
        if ($referencedProperty->isEntityCollection()) {
          $this->type = 'ManyToOne';
          $this->owning = TRUE;
        } else {
          $this->type = 'OneToOne';
          $this->owning = isset($property->getDefinition()->isOwning) && $property->getDefinition()->isOwning;
        }
      }
    }

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

    $this->orderBy = $property->hasOrderBy() ? (array) $property->getOrderBy() : NULL;
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

  public function getSlug() {
    if ($this->isUnidirectional()) {
      // i think we're always the owning side for unidirectional associations
      $format = '%s::%s => %s';

      return sprintf($format, $this->entity->getName(), $this->property->getName(), $this->referencedEntity->getName());
    } else {
      $format = '%s::%s => %s::%s';

      return sprintf($format, $this->entity->getName(), $this->property->getName(), $this->referencedEntity->getName(), $this->referencedProperty->getName());
    }
  }

  public function getPropertySlug() {
    // see model as well if this is changed
    return sprintf('%s::%s', $this->entity->getName(), $this->property->getName());
  }

  public function getReferencedPropertySlug() {
    return sprintf('%s::%s', $this->referencedEntity->getName(), $this->referencedProperty->getName());
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


  /**
   * note: this is not the same as isSelfReferecing() && isUnidrectional()
   *
   * Return true if the relation is self-referencing and uses the same property for owning and inverse side
   * @return boolean
   */
  public function isSelfReferencingUnidirectional() {
    return $this->isSelfReferencing() && isset($this->referencedProperty) && $this->referencedProperty->getName() === $this->property->getName();
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

  public function getType() {
    return $this->type;
  }

  public function hasOrderBy() {
    return isset($this->orderBy);
  }

  public function getOrderBy() {
    return $this->orderBy;
  }
}
