<?php

namespace Webforge\Code\Generator;

/**
 * A Base Class of the G*Model
 *
 */
abstract class GObject
{
   public const UNDEFINED = '::.WebforgeCodeGeneratorDefaultIsUndefined.::';

    /**
     * @var Webforge\Code\Generator\DocBlock
     */
    protected $docBlock;

    /**
     * Returns a unique key for the index in a GObjectCollection in a GClass
     *
     * @return string
     */
    abstract public function getKey();

    /**
     * @param \Webforge\Code\Generator\DocBlock $docBlock
     * @chainable
     */
    public function setDocBlock(DocBlock $docBlock)
    {
        $this->docBlock = $docBlock;
        return $this;
    }

    /**
     * Creates a new DocBlock for the class
     *
     * overwrites previous ones
     */
    public function createDocBlock($body = null)
    {
        $block = new DocBlock($body);
        $this->setDocBlock($block);
        return $block;
    }

    /**
     * Returns the DocBlock
     *
     * if no DocBlock is there, it will be created
     * @return Webforge\Code\Generator\DocBlock|NULL
     */
    public function getDocBlock()
    {
        if (!$this->hasDocBlock()) {
            $this->createDocBlock();
        }

        return $this->docBlock;
    }

    /**
     * @return bool
     */
    public function hasDocBlock()
    {
        return $this->docBlock != null;
    }
}
