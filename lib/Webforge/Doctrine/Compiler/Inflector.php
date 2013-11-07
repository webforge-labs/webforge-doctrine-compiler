<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Code\Generator\GProperty;
use stdClass;

class Inflector {

  /**
   * Returns the full setter name for a property
   * @return string
   */
  public function getPropertySetterName(GProperty $property, stdClass $definition) {
    $upcaseName = ucfirst($property->getName());

    return 'set'.$upcaseName;
  }

  /**
   * Returns the full getter name for a property
   * @return string
   */
  public function getPropertyGetterName(GProperty $property, stdClass $definition) {
    $upcaseName = ucfirst($property->getName());

    return 'get'.$upcaseName;
  }
}
