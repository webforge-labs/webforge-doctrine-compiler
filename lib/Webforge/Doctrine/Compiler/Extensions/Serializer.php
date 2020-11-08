<?php

namespace Webforge\Doctrine\Compiler\Extensions;

use JMS\Serializer\Annotation;
use stdClass;
use Webforge\Code\Generator\GClass;
use Webforge\Doctrine\Compiler\GeneratedEntity;
use Webforge\Doctrine\Compiler\GeneratedProperty;
use Webforge\Doctrine\Compiler\ModelAssociation;
use Webforge\Types\SerializationType;

class Serializer implements Extension
{
    public function __construct($annotationsWriter)
    {
        $this->annotationsWriter = $annotationsWriter;
        $this->annotationsWriter->setAnnotationNamespaceAlias('JMS\Serializer\Annotation', 'Serializer');
    }

    public function onClassGeneration(GeneratedEntity $entity, GClass $gClass)
    {
        $gClass->addImport(new GClass('JMS\Serializer\Annotation'), 'Serializer');
    }

    public function onClassAnnotationsGeneration(array &$annotations, GeneratedEntity $entity)
    {
        $annotations[] = '@Serializer\ExclusionPolicy("all")';
    }

    public function onPropertyAnnotationsGeneration(
        array &$annotations,
        GeneratedProperty $property,
        GeneratedEntity $entity
    ) {
        $defaultDefinition = $this->getDefaultPropertyDefinition($entity);

        $definition = null;
        if (!$property->hasDefinitionOf('serializer', $definition)) {
            $definition = $defaultDefinition;
        }

        $annotations[] = "@Serializer\Expose";

        if ($property->getType() instanceof SerializationType) {
            $annotations[] = sprintf('@Serializer\Type("%s")', $property->getType()->getSerializationType());
        }

        if (isset($definition->groups)) {
            $annotations[] = $annotation = new Annotation\Groups();
            $annotation->groups = $definition->groups;
        }

        if (isset($definition->inline)) {
            $annotations[] = new Annotation\Inline();
        }
    }

    protected function getDefaultPropertyDefinition(GeneratedEntity $entity)
    {
        $entityDefinition = null;
        $defaultDefinition = new stdClass();
        if ($entity->hasDefinitionOf('serializer', $entityDefinition)) {
            if (isset($entityDefinition->defaultGroups)) {
                $defaultDefinition->groups = $entityDefinition->defaultGroups;
            }
        }

        return $defaultDefinition;
    }

    public function onAssociationAnnotationsGeneration(
        array &$annotations,
        ModelAssociation $association,
        stdClass $associationPair,
        GeneratedProperty $property,
        GeneratedEntity $entity
    ) {
        if ($association->isOneToMany()) {
            $annotations[] = '@Serializer\Type("ArrayCollection")';
        } elseif ($association->isManyToOne()) {
            if (!($property->getType() instanceof SerializationType)) {
                $annotations[] = sprintf('@Serializer\Type("%s")', $association->referencedEntity->getFQN());
            }
        } elseif ($association->isManyToMany()) {
            $annotations[] = '@Serializer\Type("ArrayCollection")';
        } elseif ($association->isOneToOne()) {
            if (!($property->getType() instanceof SerializationType)) {
                $annotations[] = sprintf('@Serializer\Type("%s")', $association->referencedEntity->getFQN());
            }
        }
    }
}
