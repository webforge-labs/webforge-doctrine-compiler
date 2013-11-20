<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\ClassInterface;

interface GClassBroker {

  /**
   * Elevates the class with all properties and methods
   * 
   */
  public function getElevated(ClassInterface $class, $debugEntityName);

}
