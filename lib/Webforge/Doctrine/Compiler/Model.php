<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\ArrayUtil as A;

class Model {

  protected $namespace;
  protected $entites;

  /**
   * 
   * first level is the unique name of the relation (owning side first)
   * second level keys are "owning" or "inverse"
   * indexed by getUniqueSlug
   */
  protected $associations = array();

  // indexed by getSlug()
  protected $possibleAssociations = array();

  // indexed by getPropetySlug and getReferencedPropertySlug
  protected $possibleAssociationsIndex = array();

  /**
   * tracks the properties that already were added to a manifested association
   * @var array
   */
  protected $associatedProperties = array();

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

    foreach ($this->associations as $associationsPair) {
      $associationsPair = (object) $associationsPair;

      if (
        $associationsPair->owning->property === $property && $entity->equals($associationsPair->owning->entity) ||
        isset($associationsPair->inverse) && $associationsPair->inverse->property === $property && $entity->equals($associationsPair->inverse->entity)
        ) {
        return $associationsPair;
      }
    }

    throw new InvalidModelException(sprintf('Model has no associationsPair for %s::%s', $entity->getName(), $property->getName()));
  }

  /**
   * This method is called from entity generator for every entity of the model
   */
  public function indexAssociations(GeneratedEntity $entity) {
    foreach ($entity->getProperties() as $property) {
      if ($property->hasReference()) {
        $referencedEntity = $property->getReferencedEntity();

        if ($referencedEntity->equals($entity)) { // self referencing associations

          $this->createManifestedAssociation(
            $entity, $property,
            $referencedEntity, $property
          );
          
        } elseif (!$this->isAlreadyAssociated($entity, $property)) {

          // case: we have a relation tag that should find a matching property
          
          if ($property->hasRelationName()) {
            if ($referencedEntity->hasProperty($property->getRelationName())) {
              $referencedProperty = $referencedEntity->getProperty($property->getRelationName());

              $this->createManifestedAssociation(
                $entity, $property,
                $referencedEntity, $referencedProperty
              );

            } else {
              throw new InvalidModelException(
                sprintf(
                  'You are referencing a non existing property %s::%s in the relation of %s::%s',
                  $referencedEntity->getName(), $property->getRelationName(), $entity->getName(), $property->getName()
                )
              );
            }
          } else {
            // we have no relation tag on us, and we need to find matching candidates in the referencedEntity

            $unidirectional = TRUE;
            foreach ($referencedEntity->getProperties() as $referencedProperty) {
              if ($referencedProperty->hasReference() && $entity->equals($referencedProperty->getReferencedEntity()) && !$this->isAlreadyAssociated($referencedEntity, $referencedProperty)) {

                if ($referencedProperty->hasRelationName() && $referencedProperty->getRelationName() === $property->getName()) {
                  $this->createManifestedAssociation(
                    $entity, $property,
                    $referencedEntity, $referencedProperty
                  );
                  $unidirectional = FALSE;
                  break;

                } else {
                  // this might happen more than one time
                  $this->createPossibleAssociation(
                    $entity, $property,
                    $referencedEntity, $referencedProperty
                  );
                  $unidirectional = FALSE;
                }
              }
            }

            if ($unidirectional) {
              $this->createManifestedAssociation(
                $entity, $property,
                $referencedEntity, NULL
              );
            }
          }
        }
      }
    }
  }

  protected function createManifestedAssociation(GeneratedEntity $entity, GeneratedProperty $property, GeneratedEntity $referencedEntity, GeneratedProperty $referencedProperty = NULL) {
    $association = new ModelAssociation($entity, $property, $referencedEntity, $referencedProperty);
    $this->associations[$association->getUniqueSlug()][$association->isOwning() ? 'owning' : 'inverse'] = $association;

    // track that these properties should not be used anymore for any matching
    $this->associatedProperties[$association->getPropertySlug()] = $association;

    $propertiesSlugs = array($association->getPropertySlug());

    if (!$association->isUnidirectional()) {
      $this->associatedProperties[$association->getReferencedPropertySlug()] = $association;
      $propertiesSlugs[] = $association->getReferencedPropertySlug();

      // create the other side (might be owning or inverse), because we will mark both properties as alreadyAssociated, so that the other side would be skipped normally
      $inverse = new ModelAssociation($referencedEntity, $referencedProperty, $entity, $property);
      $this->associations[$inverse->getUniqueSlug()][$inverse->isOwning() ? 'owning' : 'inverse'] = $inverse;
    }

    //\Doctrine\Common\Util\Debug::dump($this->possibleAssociationsIndex);
    // remove possible Associations that were indexed by one of the properties in the manifested Assoc
    foreach ($propertiesSlugs as $slug) {
      if (array_key_exists($slug, $this->possibleAssociationsIndex)) {
        foreach ($this->possibleAssociationsIndex[$slug] as $possibleAssociation) {
          if (array_key_exists($possibleAssociation->getSlug(), $this->possibleAssociations)) {
            unset($this->possibleAssociations[$possibleAssociation->getSlug()]);
          }
        }
      }
    }
  }

  protected function createPossibleAssociation(GeneratedEntity $entity, GeneratedProperty $property, GeneratedEntity $referencedEntity, GeneratedProperty $referencedProperty) {
    $association = new ModelAssociation($entity, $property, $referencedEntity, $referencedProperty);

    $this->possibleAssociations[$association->getSlug()] = $association;
    $this->possibleAssociationsIndex[$association->getPropertySlug()][] = $association;
    $this->possibleAssociationsIndex[$association->getReferencedPropertySlug()][] = $association;
  }

  protected function isAlreadyAssociated(GeneratedEntity $entity, GeneratedProperty $property) {
    // see ModelAssocation as well, if this is changed
    $key = sprintf('%s::%s', $entity->getName(), $property->getName());

    return array_key_exists($key, $this->associatedProperties);
  }

  public function completeAssociations() {
    //echo "new case\n";
    //$this->debugAssociations();
    //$this->debugPossibleAssociations();

    $grouped = array();
    $index = array();
    foreach ($this->possibleAssociations as $association) {
      $grouped[ $association->getPropertySlug() ][] = $association;
      $index[$association->getPropertySlug()] = $association;
    }

    foreach ($grouped as $associations) {

      if (count($associations) > 1) {
        /* we have a conflict with non-ambigous associations (see developer docs) */

        $properties = array();
        foreach ($associations as $association) {
          $properties[$association->referencedProperty->getName()] = $association->getReferencedPropertySlug();
        }

        throw new InvalidModelException(
          sprintf(
            "You have an ambigous definition for the association %s. \n".
            "The properties: %s are pointing to %s and I dont know which property should be used.\n".
            "set \"relation\" in the definition of %s to the name of the property you want to reference.",
            $association->entity->getFQN().' => '.$association->referencedEntity->getFQN(),
            implode(', ', $properties),
            $association->entity->getName(),
            implode(' or ', $properties)
          )
        );
      }
    }

    //echo "index\n";
    //var_dump(A::keys($index));

    // from this point we have only the forward and backward associations in the possibleAssociatons, that we have to match
    // note that we cannot have unidirectional associations here, because they have been filtered out
    foreach ($index as $leftAssociation) {
      $rightAssociation = $index[$leftAssociation->getReferencedPropertySlug()];

      if (!$leftAssociation->isOwning() && !$rightAssociation->isOwning()) {
        throw new InvalidModelException(
          sprintf(
            "You have no owning side for the association %s, detected as: %s\n".
            "You have to set isOwning in the property of one of the sides of the association.\n",
            $leftAssociation->getSlug(), $leftAssociation->getType()
          )
        );
      }

      $this->associations[$leftAssociation->getUniqueSlug()] = (object) array(
        'owning'=>$leftAssociation->isOwning() ? $leftAssociation : $rightAssociation,
        'inverse'=>$leftAssociation->isOwning() ? $rightAssociation : $leftAssociation,
      );
    }

    //echo "model completed\n";
    //$this->debugAssociations();
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

  public function debugPossibleAssociations() {
    $debug = sprintf("Model has %d possibleAssociations: \n", count($this->possibleAssociations));
    foreach ($this->possibleAssociations as $association) {
      $debug .= sprintf("  %s type: %s %s\n", $association->getSlug(), $association->getType(), $association->isOwning() ? 'owning' : 'inverse');
    }
    $debug .= "\n";

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
      $this->entities[$entity->name] = $entity;
    }
  }
}
