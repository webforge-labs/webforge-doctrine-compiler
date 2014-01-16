<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Code\Generator\GProperty;
use stdClass;
use Doctrine\Common\Inflector\Inflector as DCInflector;

class Inflector {

  CONST TO_SINGULAR = 'singular';

  protected function getDoName($do, GProperty $property, stdClass $definition, $flags = 0) {
    $propertyName = $flags === self::TO_SINGULAR ? DCInflector::singularize($property->getName()) : $property->getName();
    $upcaseName = ucfirst($propertyName);

    return $do.$upcaseName;
  }

  /**
   * Returns the full setter name for a property
   * @return string
   */
  public function getPropertySetterName(GProperty $property, stdClass $definition) {
    return $this->getDoName('set', $property, $definition);
  }

  /**
   * Returns the full getter name for a property
   * @return string
   */
  public function getPropertyGetterName(GProperty $property, stdClass $definition) {
    return $this->getDoName('get', $property, $definition);
  }

  public function getCollectionAdderName(GProperty $property, stdClass $definition) {
    return $this->getDoName('add', $property, $definition, self::TO_SINGULAR);
  }

  public function getCollectionRemoverName(GProperty $property, stdClass $definition) {
    return $this->getDoName('remove', $property, $definition, self::TO_SINGULAR);
  }

  public function getCollectionCheckerName(GProperty $property, stdClass $definition) {
    return $this->getDoName('has', $property, $definition, self::TO_SINGULAR);
  }

  /**
   * 
   * e.g. in: entries => out entry
   */
  public function getItemNameFromCollectionName($collectionName, stdClass $propertyDefinition) {
    return DCInflector::singularize($collectionName);
  }

  public function tableName(stdClass $entity) {
    if (isset($entity->tableName)) {
      return $entity->tableName;
    }

    return str_replace('\\', '_', DCInflector::pluralize(DCInflector::tableize($entity->name)));
  }
}
