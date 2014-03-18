<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Code\Generator\GClass;
use Webforge\Code\Generator\GProperty;
use Webforge\Code\Generator\GParameter;
use stdClass;

class GeneratedProperty {

  protected $name;

  protected $definition;
  protected $gProperty;

  protected $setterName;
  protected $getterName;
  protected $parameter;
  protected $collectionNames;

  public function __construct(stdClass $definition, GProperty $property) {
    $this->definition = $definition;
    $this->gProperty = $property;
    $this->name = $property->getName();
  }

  public function inflect(Inflector $inflector) {
    $this->setterName = $inflector->getPropertySetterName($this->gProperty, $this->definition);
    $this->getterName = $inflector->getPropertyGetterName($this->gProperty, $this->definition);

    $this->parameter = new GParameter(
      $this->getName(),
      $this->gProperty->getType(),
      $this->definition->nullable ? NULL : GParameter::UNDEFINED
    );

    $this->collectionNames['add'] = $inflector->getCollectionAdderName($this->gProperty, $this->definition);
    $this->collectionNames['remove'] = $inflector->getCollectionRemoverName($this->gProperty, $this->definition);
    $this->collectionNames['has'] = $inflector->getCollectionCheckerName($this->gProperty, $this->definition);
  }

  public function getName() {
    return $this->name;
  }

  public function getParameter() {
    return $this->parameter;
  }

  public function getCollectionDoerName($type) {
    return $this->collectionNames[$type];
  }

  public function getDefinition() {
    return $this->definition;
  }

  public function getGProperty() {
    return $this->gProperty;
  }

  public function getSetterName() {
    return $this->setterName;
  }

  public function getGetterName() {
    return $this->getterName;
  }

  public function getReferencedEntity() {
    return $this->definition->referencedEntity;
  }

  public function isEntityCollection() {
    return isset($this->definition->reference) && $this->definition->reference instanceof EntityCollectionReference;
  }

  public function isEntity() {
    return isset($this->definition->reference) && $this->definition->reference instanceof EntityReference && !$this->definition->reference instanceof EntityCollectionReference;
  }

  public function hasReference() {
    return isset($this->definition->reference);
  }

  public function getRelationName() {
    return isset($this->definition->relation) ? $this->definition->relation : NULL;
  }

  public function isNullable() {
    return isset($this->definition->nullable) ? $this->definition->nullable : FALSE;
  }

  public function getRelationCascade() {
    return isset($this->definition->cascade) ? (array) $this->definition->cascade : NULL;
  }

  /**
   * Returns the php Documentor type for (at)param or (at)return annotations
   * 
   * @return string
   */
  public function getDocType() {
    return $this->gProperty->getType()->getDocType() ?: 'mixed';
  }

  public function getType() {
    return $this->gProperty->getType();
  }

  public function setDocBlock($docBlock) {
    $this->gProperty->setDocBlock($docBlock);
  }
}
