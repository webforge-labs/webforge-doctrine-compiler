<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use RuntimeException;

class DefinitionPart {

  public $definition;

  public function __construct(stdClass $definition) {
    $this->definition = $definition;
  }

  public function getDefinition() {
    return $this->definition;
  }

  public function hasDefinitionOf($subname, &$subDefinition = NULL) {
    if (isset($this->definition->$subname)) {
      $subDefinition = $this->definition->$subname;
      return TRUE;
    }

    return FALSE;
  }

  public function requireDefinitionOf($subname) {
    $subDefinition = NULL;
    if (!$this->hasDefinitionOf($subname, $subDefinition)) {
      throw new RuntimeException('there is no definition for: '.$subname.' in: propertyDefinition for: '.$this);
    }

    return $subDefinition;
  }

}
