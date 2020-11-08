<?php

namespace Webforge\Code;

use Webforge\Common\Exception;
use Webforge\Framework\Package\PackageNotFoundException;

class ClassFileNotFoundException extends Exception
{
    public static function fromFQN($fqn)
    {
        return new static(sprintf("The File for the class '%s' cannot be found", $fqn));
    }

    public static function fromPackageNotFoundException($fqn, PackageNotFoundException $e)
    {
        return new static(sprintf("The Class '%s' cannot be found. %s", $fqn, $e->getMessage()), 0, $e);
    }
}
