<?php

namespace Webforge\Doctrine\Compiler\Extensions;

use Webforge\Doctrine\Compiler\GeneratedProperty;
use Webforge\Doctrine\Compiler\GeneratedEntity;
use Webforge\Types\SerializationType;

class Serializer implements Extension {

  public function onPropertyAnnotationsGeneration(array &$annotations, GeneratedProperty $property, GeneratedEntity $entity) {
    $annotations[] = "@Serializer\Expose";

    if ($property->getType() instanceof SerializationType) {
      $annotations[] = sprintf('@Serializer\Type("%s")', $property->getType()->getSerializationType());
    }
  }
}
