<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\ClassInterface;
use Webforge\Code\Generator\ClassElevator;

class GClassBroker {

  protected $classElevator;

  public function __construct(ClassElevator $elevator) {
    $this->classElevator = $elevator;
  }

  /**
   * Returns a version of $class but elevated
   * 
   * Elevates the class with all properties and methods
   * its NOT $class === $returnedClass
   * @return GClass new instance
   */
  public function getElevated(ClassInterface $class, $debugEntityName) {
    $gClass = $this->classElevator->elevate($class);

    $this->classElevator->elevateParent($gClass);
    $this->classElevator->elevateInterfaces($gClass);

    return $gClass;
  }
}
