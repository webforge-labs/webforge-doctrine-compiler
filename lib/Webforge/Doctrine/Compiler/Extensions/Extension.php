<?php

namespace Webforge\Doctrine\Compiler\Extensions;

use Webforge\Doctrine\Compiler\GeneratedProperty;
use Webforge\Doctrine\Compiler\GeneratedEntity;

interface Extension {

  public function onPropertyAnnotationsGeneration(array &$annotations, GeneratedProperty $property, GeneratedEntity $entity);

}
