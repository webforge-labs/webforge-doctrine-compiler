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

  protected $owningEntity;
  protected $inflector;

  public function __construct(Inflector $inflector, GeneratedEntity $entity) {
    $this->inflector = $inflector;
    $this->owningEntity = $entity;
  }

  public function generateFor(GeneratedProperty $property) {
    // we are the one side of a relation
    if ($property->isEntityCollection()) {
      $this->generateDoer('add', $property);
      $this->generateDoer('remove', $property);
      $this->generateDoer('has', $property);
    }
  }

  protected function generateDoer($type, GeneratedProperty $property) {
    $collectionName = $property->getName();
    
    // writtenPosts => writtenPost
    $subjectName = $this->inflector->getItemNameFromCollectionName($collectionName, $property->getDefinition());
    $referencedEntity = $property->getReferencedEntity();

    $parameter = new GParameter(
      $subjectName,
      new EntityType($referencedEntity->getGClass()),
      $property->getDefinition()->nullable ? NULL : GParameter::UNDEFINED
    );

    switch ($type) {
      case 'add':
        $body = array(
          sprintf('if (!$this->%s->contains($%s)) {', $collectionName, $parameter->getName()),
          sprintf('  $this->%s->add($%s);', $collectionName, $parameter->getName()),
          '}',
          'return $this;',
        );
        break;
      case 'remove':
        $body = array(
          sprintf('if ($this->%s->contains($%s)) {', $collectionName, $parameter->getName()),
          sprintf('  $this->%s->remove($%s);', $collectionName, $parameter->getName()),
          '}',
          'return $this;',
        );
        break;
      case 'has':
        $body = array(
          sprintf('return $this->%s->contains($%s);', $collectionName, $parameter->getName()),
        );
        break;
    }


    $this->owningEntity->gClass->createMethod(
      $property->getCollectionDoerName($type),
      array(
        $parameter
      ),
      GFunctionBody::create($body),
      GMethod::MODIFIER_PUBLIC
    );
  }
}
