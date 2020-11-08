<?php

namespace Webforge\Code;

use Webforge\Common\Exception;

class ClassNotFoundException extends Exception
{
    public static function fromFQN($fqn)
    {
        return new static(sprintf("The Class '%s' cannot be found", $fqn));
    }
}
