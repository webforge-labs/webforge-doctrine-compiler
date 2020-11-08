<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Types\CollectionType;

class Model
{
    protected $namespace;

    protected $entites;

    /**
     *
     * first level is the unique name of the relation (owning side first)
     * second level keys are "owning" or "inverse"
     * indexed by getUniqueSlug
     */
    protected $associations = [];

    // indexed by getSlug()
    protected $possibleAssociations = [];

    // indexed by getPropetySlug and getReferencedPropertySlug
    protected $possibleAssociationsIndex = [];

    /**
     * tracks the properties that already were added to a manifested association
     *
     * @var array
     */
    protected $associatedProperties = [];

    /**
     *
     * first level is the slug of the associations
     * second level is a list of associations found for the two entities
     */
    protected $groupedAssociations = [];


    protected $collectionType;

    public function __construct($namespace, array $entities, $collectionType = 'default')
    {
        $this->namespace = $namespace;
        $this->indexEntities($entities);
        $this->collectionType = $collectionType;
    }

    /**
     * @return object|NULL AssocationsPair
     */
    public function getAssociationFor(GeneratedEntity $entity, GeneratedProperty $property)
    {
        foreach ($this->associations as $associationsPair) {
            $associationsPair = (object)$associationsPair;

            if (
                $associationsPair->owning->property === $property && $entity->equals($associationsPair->owning->entity)
                || isset($associationsPair->inverse) && $associationsPair->inverse->property === $property && $entity->equals(
                    $associationsPair->inverse->entity
                )
            ) {
                return $associationsPair;
            }
        }

        throw new InvalidModelException(
            sprintf(
                'Model has no associationsPair for %s::%s',
                $entity->getName(),
                $property->getName()
            )
        );
    }

    /**
     * This method is called from entity generator for every entity of the model
     */
    public function indexAssociations(GeneratedEntity $entity)
    {
        foreach ($entity->getProperties() as $property) {
            if ($property->hasReference()) {
                $referencedEntity = $property->getReferencedEntity();

                $isSelfReferencing = $referencedEntity->equals($entity);

                /*
                  $this->createManifestedAssociation(
                    $entity, $property,
                    $referencedEntity, $property
                  );
                */

                if (!$this->isAlreadyAssociated($entity, $property)) {
                    // case: we have a relation tag that should find a matching property

                    if ($property->hasRelationName()) {
                        if ($referencedEntity->hasProperty($property->getRelationName())) {
                            $referencedProperty = $referencedEntity->getProperty($property->getRelationName());

                            $this->createManifestedAssociation(
                                $entity,
                                $property,
                                $referencedEntity,
                                $referencedProperty
                            );
                        } else {
                            throw new InvalidModelException(
                                sprintf(
                                    'You are referencing a non existing property %s::%s in the relation of %s::%s',
                                    $referencedEntity->getName(),
                                    $property->getRelationName(),
                                    $entity->getName(),
                                    $property->getName()
                                )
                            );
                        }
                    } else {
                        // case: we have no relation tag on us, and we need to find matching candidates in the referencedEntity

                        $unidirectional = true;
                        foreach ($referencedEntity->getProperties() as $referencedProperty) {
                            if (
                                $referencedProperty->hasReference() && $entity->equals(
                                    $referencedProperty->getReferencedEntity()
                                ) && !$this->isAlreadyAssociated(
                                    $referencedEntity,
                                    $referencedProperty
                                )
                            ) {
                                if (
                                    $referencedProperty->hasRelationName() && $referencedProperty->getRelationName(
                                    ) === $property->getName()
                                ) {
                                    $this->createManifestedAssociation(
                                        $entity,
                                        $property,
                                        $referencedEntity,
                                        $referencedProperty
                                    );
                                    $unidirectional = false;
                                    break;
                                } else {
                                    // this might happen more than one time
                                    $this->createPossibleAssociation(
                                        $entity,
                                        $property,
                                        $referencedEntity,
                                        $referencedProperty
                                    );
                                    $unidirectional = false;
                                }
                            }
                        }

                        if ($unidirectional) {
                            $this->createManifestedAssociation(
                                $entity,
                                $property,
                                $referencedEntity,
                                null
                            );
                        }
                    }
                }
            }
        }
    }

    protected function createManifestedAssociation(
        GeneratedEntity $entity,
        GeneratedProperty $property,
        GeneratedEntity $referencedEntity,
        GeneratedProperty $referencedProperty = null
    ) {
        $association = new ModelAssociation($entity, $property, $referencedEntity, $referencedProperty);
        $this->associations[$association->getUniqueSlug()][$association->isOwning(
        ) ? 'owning' : 'inverse'] = $association;

        // track that these properties should not be used anymore for any matching
        $this->associatedProperties[$association->getPropertySlug()] = $association;

        $propertiesSlugs = [$association->getPropertySlug()];

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

    protected function createPossibleAssociation(
        GeneratedEntity $entity,
        GeneratedProperty $property,
        GeneratedEntity $referencedEntity,
        GeneratedProperty $referencedProperty
    ) {
        $association = new ModelAssociation($entity, $property, $referencedEntity, $referencedProperty);

        $this->possibleAssociations[$association->getSlug()] = $association;
        $this->possibleAssociationsIndex[$association->getPropertySlug()][] = $association;
        $this->possibleAssociationsIndex[$association->getReferencedPropertySlug()][] = $association;
    }

    protected function isAlreadyAssociated(GeneratedEntity $entity, GeneratedProperty $property)
    {
        // see ModelAssocation as well, if this is changed
        $key = sprintf('%s::%s', $entity->getName(), $property->getName());

        return array_key_exists($key, $this->associatedProperties);
    }

    public function completeAssociations()
    {
        /*
        echo "new case\n";
        $this->debugAssociations();
        $this->debugPossibleAssociations();
        */

        $grouped = [];
        $index = [];
        foreach ($this->possibleAssociations as $association) {
            $grouped[$association->getPropertySlug()][] = $association;
            $index[$association->getPropertySlug()] = $association;
        }

        foreach ($grouped as $associations) {
            if (count($associations) > 1) {
                /* we have a conflict with non-ambigous associations (see developer docs) */

                $properties = [];
                foreach ($associations as $association) {
                    $properties[$association->referencedProperty->getName()] = $association->getReferencedPropertySlug(
                    );
                }

                throw new InvalidModelException(
                    sprintf(
                        "You have an ambigous definition for the association %s. \n" .
                        "The properties: %s are pointing to %s and I dont know which property should be used.\n" .
                        "set \"relation\" in the definition of %s to the name of the property you want to reference.",
                        $association->entity->getFQN() . ' => ' . $association->referencedEntity->getFQN(),
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
                        "You have no owning side for the association %s, detected as: %s\n" .
                        "You have to set isOwning in the property of one of the sides of the association.\n",
                        $leftAssociation->getSlug(),
                        $leftAssociation->getType()
                    )
                );
            }

            $this->associations[$leftAssociation->getUniqueSlug()] = (object)[
                'owning' => $leftAssociation->isOwning() ? $leftAssociation : $rightAssociation,
                'inverse' => $leftAssociation->isOwning() ? $rightAssociation : $leftAssociation,
            ];
        }

        //echo "model completed\n";
        //$this->debugAssociations();
    }

    // @codeCoverageIgnoreStart
    public function debugAssociations()
    {
        $debug = sprintf("Model has %d unique associations: \n", count($this->associations));
        foreach ($this->associations as $associationPair) {
            foreach ($associationPair as $owningType => $association) {
                $debug .= sprintf("  %s (%s)\n", $association->getSlug(), $owningType);
            }
            $debug .= "\n";
        }

        print $debug;
    }

    public function debugPossibleAssociations()
    {
        $debug = sprintf("Model has %d possibleAssociations: \n", count($this->possibleAssociations));
        foreach ($this->possibleAssociations as $association) {
            $debug .= sprintf(
                "  %s type: %s %s\n",
                $association->getSlug(),
                $association->getType(),
                $association->isOwning() ? 'owning' : 'inverse'
            );
        }
        $debug .= "\n";

        print $debug;
    }

    // @codeCoverageIgnoreEnd

    public function getEntities()
    {
        return $this->entities;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getEntity($name)
    {
        return $this->entities[$name];
    }

    public function hasEntity($name)
    {
        return array_key_exists($name, $this->entities);
    }

    public function getCollectionImplementation()
    {
        if ($this->collectionType === 'psc-cms') {
            return CollectionType::PSC_ARRAY_COLLECTION;
        }

        if ($this->collectionType === 'doctrine') {
            return CollectionType::DOCTRINE_ARRAY_COLLECTION;
        }

        return CollectionType::WEBFORGE_COLLECTION;
    }

    protected function indexEntities(array $entities)
    {
        $this->entities = [];
        foreach ($entities as $entity) {
            $this->entities[$entity->name] = $entity;
        }
    }
}
