<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;

class EntityReference
{
    protected $definition;

    public function __construct(stdClass $entityDefinition)
    {
        $this->definition = $entityDefinition;
    }

    public function getFQN()
    {
        return $this->definition->fqn;
    }
}
