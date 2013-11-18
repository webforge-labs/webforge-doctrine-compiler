<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\ClassUtil;
use Webforge\Common\ArrayUtil as A;

class Model {

  protected $namespace;
  protected $entites;

  /**
   * 
   * first level is the unique name of the relation (owning side first)
   * second level keys are "owning" or "inverse"
   */
  protected $associations = array();

  /**
   * 
   * first level is the slug of the associations
   * second level is a list of associations found for the two entities
   */
  protected $groupedAssociations = array();

  public function __construct($namespace, Array $entities) {
    $this->namespace = $namespace;
    $this->indexEntities($entities);
  }

  /**
   * @return object|NULL AssocationsPair
   */
  public function getAssociationFor(GeneratedEntity $entity, GeneratedProperty $property) {
    $referencedEntity = $property->getReferencedEntity();

    foreach ($this->associations as $associationsPair) {
      if (
        $associationsPair->owning->property === $property && $entity->equals($associationsPair->owning->entity) ||
        $associationsPair->inverse->property === $property && $entity->equals($associationsPair->inverse->entity)
        ) {
        return $associationsPair;
      }
    }
  }

  public function indexAssociations(GeneratedEntity $entity) {
    foreach ($entity->getProperties() as $property) {
      if ($property->hasReference()) {
        $referencedEntity = $property->getReferencedEntity();

        $unidirectional = TRUE;
        foreach ($referencedEntity->getProperties() as $referencedProperty) {
          if ($referencedProperty->hasReference() && $entity->equals($referencedProperty->getReferencedEntity())) {
            $type = $owning = NULL;
            
            if ($property->isEntityCollection()) {
              if ($referencedProperty->isEntityCollection()) {
                $type = 'ManyToMany';
                $owning = isset($property->getDefinition()->isOwning) && $property->getDefinition()->isOwning; // this might produce a conflict that no side isOwning (check chis later)
              } else {
                $type = 'OneToMany';
                $owning = FALSE;
              }
            } elseif ($property->isEntity()) {
              if ($referencedProperty->isEntityCollection()) {
                $type = 'ManyToOne';
                $owning = TRUE;
              } else {
                $type = 'OneToOne';
                $owning = isset($property->getDefinition()->isOwning) && $property->getDefinition()->isOwning;
              }
            }

            $association = new ModelAssociation($type, $entity, $property, $referencedEntity, $referencedProperty);
            $association->setOwning($owning);

            $this->indexAssociation($association);
            $unidirectional = FALSE;
          }
        }

        if ($unidirectional) {
          $referencedProperty = NULL;

          if ($property->isEntityCollection()) {
            $type = 'ManyToMany';
          } elseif ($property->isEntity()) {
            $type = 'OneToOne';
          }

          $association = new ModelAssociation($type, $entity, $property, $referencedEntity, $referencedProperty);
          $association->setOwning(TRUE);
          
          $this->indexAssociation($association);
        }
      }
    }
  }

  protected function indexAssociation(ModelAssociation $association) {
    $this->associations[$association->getUniqueSlug()][$association->isOwning() ? 'owning' : 'inverse'] = $association;
  }

  public function completeAssociations() {
    //var_dump(A::keys($this->associations));

    $grouped = array();
    foreach ($this->associations as $key=>$associationPair) {
      $owningAssociation = $associationPair['owning'];
      $grouped[sprintf('%s::%s', $owningAssociation->entity->getName(), $owningAssociation->property->getName())][] = (object) $associationPair;
    }

    $this->associations = array();
    foreach ($grouped as $groupKey => $associationPairs) {
      //print "investigating group: ".$groupKey."\n";

      if (count($associationPairs) > 1) {
        /* we have a conflict with non-ambigous associations (see developer docs) */

        $foundPair = NULL; // the one that has the matching relation (can be found or not)
        $associationPairs = array_filter($associationPairs, function($associationPair) use (&$foundPair) {
          $inverse = $associationPair->inverse;

          //print $inverse->getUniqueSlug()."\n";
          //var_dump($inverse->property->getRelationName(), $inverse->referencedProperty->getName());

          if (($relation = $inverse->property->getRelationName()) != NULL) {
            if ($relation === $inverse->referencedProperty->getName()) {
              $foundPair = $associationPair;
              return TRUE;
            } else {
              return FALSE;
            }
          }

          return TRUE;
        });

        if ($foundPair) {
          $associationPairs = array($foundPair);
        }
      }

      if (count($associationPairs) > 1) {
        $properties = array();
        foreach ($associationPairs as $associationPair) {
          $association = $associationPair->owning;
          $properties[$association->referencedProperty->getName()] = $association->referencedEntity->getName().'::'.$association->referencedProperty->getName();
        }

        throw new InvalidModelException(
          sprintf(
            "You have an ambigous definition for the association %s. \n".
            "The properties: %s are both pointing to %s and I dont know which property should be used.\n".
            "set \"relation\" in the definition of %s to the name of the property you want to reference.",
            $association->entity->getFQN().' => '.$association->referencedEntity->getFQN(),
            implode(', ', $properties),
            $association->entity->getName(),
            implode(' or ', $properties)
          )
        );
      } elseif(count($associationPairs) === 1) {
        $associationPair = current($associationPairs);

        $this->associations[$associationPair->owning->getUniqueSlug()] = $associationPair;
        //print "reduced group\n";
      }

      //print "\n";
    }
  }

  // @codeCoverageIgnoreStart
  public function debugAssociations() {
    $debug = sprintf("Model has %d unique associations: \n", count($this->associations));
    foreach ($this->associations as $associationPair) {
      foreach ($associationPair as $owningType => $association) {
        $debug .= sprintf("  %s (%s)\n", $association->getSlug(), $owningType);
      }
      $debug .= "\n";
    }

    print $debug;
  }
  // @codeCoverageIgnoreEnd

  public function getEntities() {
    return $this->entities;
  }

  public function getNamespace() {
    return $this->namespace;
  }

  public function getEntity($name) {
    return $this->entities[$name];
  }

  public function hasEntity($name) {
    return array_key_exists($name, $this->entities);
  }

  protected function indexEntities(Array $entities) {
    $this->entities = array();
    foreach($entities as $entity) {
      $entity->fqn = ClassUtil::expandNamespace($entity->name, $this->getNamespace());
      $this->entities[$entity->name] = $entity;
    }
  }
}
