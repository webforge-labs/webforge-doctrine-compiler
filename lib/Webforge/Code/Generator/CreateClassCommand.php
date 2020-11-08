<?php

namespace Webforge\Code\Generator;

use Closure;
use Webforge\Common\ClassUtil;
use Webforge\Common\System\File;
use Webforge\Framework\Container;
use Webforge\Framework\Package\Package;

/**
 * Easier usage of the classCreater
 */
class CreateClassCommand
{
    /**
     * @var Webforge\Code\Generator\ClassCreater
     */
    protected $classCreater;

    /**
     * @var Webforge\Code\Generator\ClassFileMapper
     */
    protected $classFileMapper;

    /**
     * is available after fqn()
     * @var Webforge\Code\Generator\GClass
     */
    protected $gClass;

    /**
     * is available after write()
     * @var Webforge\Common\System\File
     */
    protected $file;

    /**
     * Namspace will be appended to classes created with name()
     *
     * @var string without backslash at end
     */
    protected $defaultNamespace;

    public function __construct(ClassCreater $classCreater, ClassFileMapper $classFileMapper, $defaultNamespace)
    {
        $this->classCreater = $classCreater;
        $this->classFileMapper = $classFileMapper;
        $this->defaultNamespace = rtrim($defaultNamespace, '\\');
    }

    /**
     * @return Webforge\Code\Generator\CreateClassCommand
     */
    public static function fromContainer(Container $container, $defaultNamespace = null)
    {
        return new static(
            new ClassCreater(
                $container->getClassFileMapper(),
                $container->getClassWriter(),
                $container->getClassElevator()
            ),
            $container->getClassFileMapper(),
            $defaultNamespace ?: $container->getLocalPackage()->getNamespace()
        );
    }

    public function reset()
    {
        $this->file = null;
        $this->gClass = null;
        return $this;
    }

    /**
     * @chainable
     */
    public function fqn($fqn)
    {
        $this->reset();
        $this->gClass = new GClass($fqn);
        return $this;
    }

    /**
     * @chainable
     */
    public function name($relativeClassName)
    {
        return $this->fqn(ClassUtil::setNamespace($relativeClassName, $this->defaultNamespace));
    }

    /**
     * @chainable
     */
    public function parent($fqn)
    {
        $this->gClass->setParent(new GClass($fqn));
        return $this;
    }

    /**
     * @chainable
     */
    public function addInterface($fqn)
    {
        $this->gClass->addInterface(new GInterface($fqn));
        return $this;
    }

    /**
     *
     * @param Closure $do function(GClass $gClass)
     * @chainable
     */
    public function withGClass(Closure $do)
    {
        $do($this->gClass);
        return $this;
    }

    public function setFileFromPackage(Package $package)
    {
        $this->file = $this->classFileMapper->findWithPackage(
            $this->gClass->getFQN(),
            $package
        );
        return $this;
    }

    public function setWriteFile(File $file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return Webforge\Code\Generator\GClass
     */
    public function getGClass()
    {
        return $this->gClass;
    }

    /**
     * @return Webforge\Common\System\File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @chainable
     */
    public function write($overwrite = false)
    {
        $this->file = $this->classCreater->create(
            $this->gClass,
            $overwrite ? ClassCreater::OVERWRITE : false,
            $this->file
        );

        return $this;
    }

    /**
     * @chainable
     */
    public function overwrite()
    {
        return $this->write(true);
    }
}
