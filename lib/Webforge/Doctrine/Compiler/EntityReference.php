<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;

class EntityReference {

  protected $definition;
  protected $name;

  public function __construct(stdClass $entityDefinition, $name = NULL) {
    $this->definition = $entityDefinition;
    $this->name = $name;
  }

  public function getName() {
    return $this->name;
  }

  public function setName() {
    return $this->name;
  }

  public function getFQN() {
    return $this->definition->fqn;
  }
}
