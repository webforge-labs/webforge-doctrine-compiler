<?php

namespace Webforge\Doctrine\Compiler\Types;

use Webforge\Types\DoctrineExportableType;
use Webforge\Types\Type;

class JsonType extends Type implements DoctrineExportableType
{
    public function getDocType()
    {
        return 'array';
    }

    /**
     * Returns a string that can be used as @Doctrine\ORM\Mapping\Column(type="%s")
     */
    public function getDoctrineExportType()
    {
        return 'json';
    }
}
