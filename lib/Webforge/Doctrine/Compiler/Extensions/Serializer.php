<?php

namespace Webforge\Doctrine\Compiler\Extensions;

use Webforge\Doctrine\Compiler\GeneratedProperty;
use Webforge\Code\Generator\GClass;
use Webforge\Doctrine\Compiler\GeneratedEntity;
use Webforge\Types\SerializationType;

class Serializer implements Extension {

  public function __construct($annotationsWriter) {
    $this->annotationsWriter = $annotationsWriter;
    $this->annotationsWriter->setAnnotationNamespaceAlias('JMS\Serializer\Annotation', 'Serializer');
  }

  public function onClassGeneration(GeneratedEntity $entity, GClass $gClass) {
    $gClass->addImport(new GClass('JMS\Serializer\Annotation'), 'Serializer');
  }

  public function onClassAnnotationsGeneration(array &$annotations, GeneratedEntity $entity) {
    $annotations[] = '@Serializer\ExclusionPolicy("all")';
  }

  public function onPropertyAnnotationsGeneration(array &$annotations, GeneratedProperty $property, GeneratedEntity $entity) {
    $annotations[] = "@Serializer\Expose";

    if ($property->getType() instanceof SerializationType) {
      $annotations[] = sprintf('@Serializer\Type("%s")', $property->getType()->getSerializationType());
    }

    $definition = NULL;
    if ($property->hasDefinitionOf('serializer', $definition)) {
      if (isset($definition->groups)) {
        //$annotations[] = sprintf('@Serializer\Groups');
      }
    }
  }
}
