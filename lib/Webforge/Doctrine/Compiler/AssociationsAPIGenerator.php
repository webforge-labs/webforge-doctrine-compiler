<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Code\Generator\GClass;
use Webforge\Code\Generator\GProperty;
use Webforge\Code\Generator\GParameter;
use Webforge\Code\Generator\GFunctionBody;
use Webforge\Code\Generator\GMethod;
use stdClass;
use Webforge\Types\PersistentCollectionType;

class AssociationsAPIGenerator {

  protected $gClass, $owningEntity;
  protected $inflector;

  public function __construct(Inflector $inflector, GClass $gClass, stdClass $owningEntity, stdClass $subjectEntity) {
    $this->inflector = $inflector;
    $this->gClass = $gClass;
    $this->owningEntity = $owningEntity;
    $this->subjectEntity = $subjectEntity;
  }

  public function generateFor(GProperty $property, stdClass $definition) {
    // we are the one side of a relation
    if ($property->getType() instanceof PersistentCollectionType) {
      $this->generateDoer('add', $property, $definition);
      $this->generateDoer('remove', $property, $definition);
      $this->generateDoer('has', $property, $definition);
    }
  }

  protected function generateDoer($type, GProperty $property, $definition) {
    $collectionName = $property->getName();
    $subjectName = $this->inflector->getItemNameFromCollectionName($collectionName, $definition);
    $subjectDefinition = $this->owningEntity->properties->$subjectName;
    $subject = $this->gClass->getProperty($subjectDefinition->name);

    switch ($type) {
      case 'add':
        $methodName = $this->inflector->getCollectionAdderName($property, $definition);
        $body = array(
          sprintf('if (!$this->%s->contains($%s)) {', $collectionName, $subject->getName()),
          sprintf('  $this->%s->add($%s);', $collectionName, $subject->getName()),
          '}',
          'return $this;',
        );
        break;
      case 'remove':
        $methodName = $this->inflector->getCollectionRemoverName($property, $definition);
        $body = array(
          sprintf('if ($this->%s->contains($%s)) {', $collectionName, $subject->getName()),
          sprintf('  $this->%s->remove($%s);', $collectionName, $subject->getName()),
          '}',
          'return $this;',
        );
        break;
      case 'has':
        $methodName = $this->inflector->getCollectionCheckerName($property, $definition);
        $body = array(
          sprintf('return $this->%s->contains($%s);', $collectionName, $subject->getName()),
        );
        break;
    }

    $this->gClass->createMethod(
      $methodName,
      array(
        $parameter = new GParameter(
          $subject->getName(),
          $subject->getType(),
          $definition->nullable ? NULL : GParameter::UNDEFINED
        )
      ),
      GFunctionBody::create($body),
      GMethod::MODIFIER_PUBLIC
    );
  }
}
