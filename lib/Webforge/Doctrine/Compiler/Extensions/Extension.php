<?php

namespace Webforge\Doctrine\Compiler\Extensions;

use stdClass;
use Webforge\Code\Generator\GClass;
use Webforge\Doctrine\Compiler\GeneratedEntity;
use Webforge\Doctrine\Compiler\GeneratedProperty;
use Webforge\Doctrine\Compiler\ModelAssociation;

interface Extension
{
    public function onPropertyAnnotationsGeneration(
        array &$annotations,
        GeneratedProperty $property,
        GeneratedEntity $entity
    );

    public function onClassGeneration(GeneratedEntity $entity, GClass $gClass);

    public function onClassAnnotationsGeneration(array &$annotations, GeneratedEntity $entity);

    public function onAssociationAnnotationsGeneration(
        array &$annotations,
        ModelAssociation $association,
        stdClass $associationPair,
        GeneratedProperty $property,
        GeneratedEntity $entity
    );
}
