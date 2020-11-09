<?php

namespace Webforge\Doctrine\Compiler;

use RuntimeException;
use stdClass;

class DefinitionPart
{
    public $definition;

    public function __construct(stdClass $definition)
    {
        $this->definition = $definition;
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function hasDefinitionOf($subname, &$subDefinition = null)
    {
        if (isset($this->definition->$subname)) {
            $subDefinition = $this->definition->$subname;
            return true;
        }

        return false;
    }

    public function requireDefinitionOf($subname)
    {
        $subDefinition = null;
        if (!$this->hasDefinitionOf($subname, $subDefinition)) {
            throw new RuntimeException(
                'there is no definition for: ' . $subname . ' in: propertyDefinition for: ' . $this
            );
        }

        return $subDefinition;
    }
}
