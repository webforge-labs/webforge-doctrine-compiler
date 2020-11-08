<?php

namespace Webforge\Code\Generator;

use Composer\Autoload\ClassLoader;
use InvalidArgumentException;
use Webforge\Code\ClassFileNotFoundException;
use Webforge\Common\Exception\NotImplementedException;
use Webforge\Common\System\File;

class ComposerClassFileMapper implements ClassFileMapper
{
    protected $autoLoader;

    public function __construct(ClassLoader $autoLoader)
    {
        $this->autoLoader = $autoLoader;
    }

    /**
     * @return Webforge\Common\System\File
     */
    public function getFile($fqn)
    {
        $fqn = $this->normalizeClassFQN($fqn);

        if (is_string($file = $this->autoLoader->findFile($fqn))) {
            return new File($file);
        }

        throw ClassFileNotFoundException::fromFQN($fqn);
    }

    public function getClass(File $file)
    {
        throw NotImplementedException::fromString('getting the class from a file');
    }

    // @TODO refactor into abstractClassMapper
    protected function normalizeClassFQN($fqn)
    {
        $fqn = ltrim($fqn, '\\');

        if (mb_strlen($fqn) === 0) {
            throw new InvalidArgumentException('fqn cannot be empty');
        }

        return $fqn;
    }
}
