<?php

namespace Webforge\Code\Generator;

class ClassElevator
{
    /**
     * @var Webforge\Code\Generator\ClassFileMapper
     */
    protected $mapper;

    /**
     * @var Webforge\Code\Generator\ClassReader
     */
    protected $reader;

    public function __construct(ClassFileMapper $mapper, ClassReader $reader)
    {
        $this->mapper = $mapper;
        $this->reader = $reader;
    }

    /**
     * @return GClass
     */
    public function getGClass($fqn)
    {
        $this->elevate($gClass = new GClass($fqn));
        return $gClass;
    }

    /**
     *
     * @return GClass but not the same as argument
     */
    public function elevate(GClass $gClass)
    {
        return $this->reader->readInto(
            $this->mapper->getFile($gClass->getFQN()),
            $gClass
        );
    }

    /**
     * @return GClass
     */
    public function elevateParent(GClass $child)
    {
        // @TODO what is about parents from parent?
        if (($parent = $child->getParent()) != null) {
            $this->elevate($parent);
        }

        return $child;
    }

    /**
     * @return GClass
     */
    public function elevateInterfaces(GClass $gClass)
    {
        foreach ($gClass->getAllInterfaces() as $interface) {
            $this->elevate($interface);
        }
        return $gClass;
    }

    public function getClassReader()
    {
        return $this->reader;
    }

    public function getClassFileMapper()
    {
        return $this->mapper;
    }
}
