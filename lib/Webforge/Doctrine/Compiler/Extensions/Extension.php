<?php

namespace Webforge\Doctrine\Compiler\Extensions;

use Webforge\Doctrine\Compiler\GeneratedProperty;
use Webforge\Doctrine\Compiler\GeneratedEntity;
use Webforge\Code\Generator\GClass;

interface Extension {

  public function onPropertyAnnotationsGeneration(array &$annotations, GeneratedProperty $property, GeneratedEntity $entity);

  public function onClassGeneration(GeneratedEntity $entity, GClass $gClass);

  public function onClassAnnotationsGeneration(array &$annotations, GeneratedEntity $entity);

}
