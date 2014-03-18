<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Code\Generator\GClass;
use Webforge\Code\Generator\GProperty;
use Webforge\Code\Generator\GParameter;
use Webforge\Code\Generator\GFunctionBody;
use Webforge\Code\Generator\GMethod;
use stdClass;
use Webforge\Types\PersistentCollectionType;
use Webforge\Types\EntityType;

class AssociationsAPIGenerator {

  protected $entity;
  protected $inflector;

  public function __construct(Inflector $inflector, GeneratedEntity $entity) {
    $this->inflector = $inflector;
    $this->entity = $entity;
  }

  public function generateFor(stdClass $associationsPair, GeneratedProperty $property) {
    if ($this->entity->equals($associationsPair->owning->entity)) {
      $association = $associationsPair->owning;
    } else {
      $association = $associationsPair->inverse;
    }
    
    if ($property->isEntity()) {
      $this->injectIntoSetter($property, $association);
    }
    
    if ($property->isEntityCollection()) {
      $this->generateDoer('add', $property, $association);
      $this->generateDoer('remove', $property, $association);
      $this->generateDoer('has', $property, $association);
    }
  }

  protected function generateDoer($type, GeneratedProperty $property, ModelAssociation $association) {
    $collectionName = $property->getName();

    // writtenPosts => writtenPost
    $paramName = $this->inflector->getItemNameFromCollectionName($collectionName, $property->getDefinition());

    $parameter = new GParameter(
      $paramName,
      new EntityType($association->referencedEntity->getGClass()),
      $property->getDefinition()->nullable ? NULL : GParameter::UNDEFINED
    );

    $updateOtherside = $association->shouldUpdateOtherSide();

    $body = array();
    switch ($type) {
      case 'add':
        $body[] = sprintf('if (!$this->%s->contains($%s)) {', $collectionName, $parameter->getName());
        $body[] = sprintf('  $this->%s->add($%s);', $collectionName, $parameter->getName());
        if ($updateOtherside) {
          $body[] = sprintf('  $%s->%s($this);', $parameter->getName(), $association->referencedProperty->getCollectionDoerName('add'));
        }
        $body[] = '}';
        $body[] = 'return $this;';
        break;

      case 'remove':
        $body[] = sprintf('if ($this->%s->contains($%s)) {', $collectionName, $parameter->getName());
        $body[] = sprintf('  $this->%s->removeElement($%s);', $collectionName, $parameter->getName());
        if ($updateOtherside) {
          $body[] = sprintf('  $%s->%s($this);', $parameter->getName(), $association->referencedProperty->getCollectionDoerName('remove'));
        }
        $body[] = '}';
        $body[] = 'return $this;';
        break;

      case 'has':
        $body = array(
          sprintf('return $this->%s->contains($%s);', $collectionName, $parameter->getName()),
        );
        break;
    }

    $this->entity->gClass->createMethod(
      $property->getCollectionDoerName($type),
      array(
        $parameter
      ),
      GFunctionBody::create($body),
      GMethod::MODIFIER_PUBLIC
    );
  }

  protected function injectIntoSetter(GeneratedProperty $property, ModelAssociation $association) {
    if ($association->shouldUpdateOtherSide()) {
      $setter = $this->entity->gClass->getMethod($property->getSetterName());

      $code[] = sprintf('$%s->%s($this);', $setter->getParameterByIndex(0)->getName(), $association->referencedProperty->getCollectionDoerName('add'));

      $setter->getBody()->insertBody($code, 1);
    }
  }
}
