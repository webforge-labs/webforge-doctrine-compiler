<?php

namespace Webforge\Code\Generator;

use ReflectionException;
use Webforge\Code\ClassFileNotFoundException;
use Webforge\Common\System\File;

/**
 * @TODO create a logger / output interfaces to warn when class elevating does not work
 */
class ClassCreater
{
   public const OVERWRITE = ClassWriter::OVERWRITE;

    /**
     * @var \Webforge\Code\Generator\ClassFileMapper
     */
    protected $mapper;

    /**
     * @var GClass
     */
    protected $gClass;

    /**
     * @var \Webforge\Code\Generator\ClassWriter
     */
    protected $writer;

    /**
     * @var \Webforge\Code\Generator\ClassElevator
     */
    protected $elevator;

    public function __construct(ClassFileMapper $mapper, ClassWriter $writer, ClassElevator $elevator)
    {
        $this->mapper = $mapper;
        $this->writer = $writer;
        $this->elevator = $elevator;
    }

    /**
     * Creates a new Class and writes it to a file
     *
     * @param GClass $gClass
     * @param File $file if not given the ClassFileMapper will be asked for a file
     * @return Webforge\Common\System\File
     */
    public function create(GClass $gClass, $overwrite = false, File $file = null)
    {
        $file = $file ?: $this->mapper->getFile($gClass->getFQN());

        $file->getDirectory()->create();

        try {
            $this->elevator->elevateParent($gClass);
        } catch (ClassFileNotFoundException $e) {
        } catch (ReflectionException $e) {
        }

        try {
            $this->elevator->elevateInterfaces($gClass);
        } catch (ClassFileNotFoundException $e) {
        } catch (ReflectionException $e) {
        }


        $gClass->createAbstractMethodStubs();

        $this->writer->write($gClass, $file, $overwrite);

        return $file;
    }

    /**
     * @chainable
     */
    public function setClassElevator(ClassElevator $elevator)
    {
        $this->elevator = $elevator;
        return $this;
    }
}
