<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Code\Generator\GClass;
use stdClass;

class GeneratedEntity {

  public $gClass;

  public $definition;

  protected $parent;
  protected $properties = array();

  protected $tableName;

  public function __construct(stdClass $definition, GClass $gClass) {
    $this->gClass = $gClass;
    $this->definition = $definition;
  }

  public function inflect(Inflector $inflector) {
    $this->tableName = $inflector->tableName($this->definition);
  }

  public function setParent($parent) {
    $this->parent = $parent;
    $gClass = $parent instanceof GeneratedEntity ? $parent->gClass : $parent;
    $this->gClass->setParent($gClass);
  }

  public function getParent() {
    return $this->parent;
  }

  public function getProperty($name) {
    return $this->properties[$name];
  }

  public function hasProperty($name) {
    return array_key_exists($this->properties, $name);
  }

  // be sure to connnect with gClass yourself
  public function addProperty(GeneratedProperty $property) {
    $this->properties[$property->getName()] = $property;
  }

  public function getProperties() {
    return $this->properties;
  }

  public function getGClass() {
    return $this->gClass;
  }

  public function getFQN() {
    return $this->gClass->getFQN();
  }

  public function getName() {
    return $this->gClass->getName();
  }

  public function equals(GeneratedEntity $otherEntity) {
    return $otherEntity->getFQN() === $this->getFQN();
  }

  public function getTableName() {
    return $this->tableName;
  }

  public function getIdentifierColumn() {
    return 'id';
  }

  public function getDescription() {
    return isset($this->definition->description) ? $this->definition->description : NULL;
  }
}
