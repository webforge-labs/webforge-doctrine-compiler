<?php

namespace Webforge\Code\Generator;

use ReflectionMethod;
use ReflectionProperty;

/**
 * A base Class for all GObjects with modifiers
 *
 * these are:
 *
 * abstract
 * final
 * static
 *
 * public
 * protected
 * private
 *
 * of course: a GProperty is not abstract, but DRY
 *
 * notice:
 * it is technical possible to set PUBLIC, PRIVATE, PROTECTED at the same time.
 * But it does not make sense...
 */
abstract class GModifiersObject extends GObject
{
    public const MODIFIER_STATIC = ReflectionProperty::IS_STATIC;
    public const MODIFIER_ABSTRACT = ReflectionMethod::IS_ABSTRACT;
    public const MODIFIER_FINAL = ReflectionMethod::IS_FINAL;

    public const MODIFIER_PUBLIC = ReflectionProperty::IS_PUBLIC;
    public const MODIFIER_PROTECTED = ReflectionProperty::IS_PROTECTED;
    public const MODIFIER_PRIVATE = ReflectionProperty::IS_PRIVATE;

    /**
     * @var int a bitmap of the self::MODIFIER_* constants
     */
    protected $modifiers;

    /**
     * @return bool
     */
    public function isStatic()
    {
        return ($this->modifiers & self::MODIFIER_STATIC) == self::MODIFIER_STATIC;
    }

    /**
     * @return bool
     */
    public function isAbstract()
    {
        return ($this->modifiers & self::MODIFIER_ABSTRACT) == self::MODIFIER_ABSTRACT;
    }

    /**
     * @return bool
     */
    public function isFinal()
    {
        return ($this->modifiers & self::MODIFIER_FINAL) == self::MODIFIER_FINAL;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return ($this->modifiers & self::MODIFIER_PUBLIC) == self::MODIFIER_PUBLIC;
    }

    /**
     * @return bool
     */
    public function isProtected()
    {
        return ($this->modifiers & self::MODIFIER_PROTECTED) == self::MODIFIER_PROTECTED;
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return ($this->modifiers & self::MODIFIER_PRIVATE) == self::MODIFIER_PRIVATE;
    }

    /**
     * @param bool $bool
     */
    public function setAbstract($bool = true)
    {
        if ($bool) {
            $this->modifiers |= self::MODIFIER_ABSTRACT;
        } else {
            $this->modifiers &= ~self::MODIFIER_ABSTRACT;
        }
        return $this;
    }

    /**
     * @param bool $bool
     */
    public function setStatic($bool = true)
    {
        if ($bool) {
            $this->modifiers |= self::MODIFIER_STATIC;
        } else {
            $this->modifiers &= ~self::MODIFIER_STATIC;
        }
        return $this;
    }

    /**
     * @param bool $bool
     */
    public function setFinal($bool = true)
    {
        if ($bool) {
            $this->modifiers |= self::MODIFIER_FINAL;
        } else {
            $this->modifiers &= ~self::MODIFIER_FINAL;
        }
        return $this;
    }

    /**
     * @param bool $bool
     */
    public function setPublic($bool = true)
    {
        if ($bool) {
            $this->modifiers |= self::MODIFIER_PUBLIC;
        } else {
            $this->modifiers &= ~self::MODIFIER_PUBLIC;
        }
        return $this;
    }

    /**
     * @param bool $bool
     */
    public function setProtected($bool = true)
    {
        if ($bool) {
            $this->modifiers |= self::MODIFIER_PROTECTED;
        } else {
            $this->modifiers &= ~self::MODIFIER_PROTECTED;
        }
        return $this;
    }

    /**
     * @param bool $bool
     */
    public function setPrivate($bool = true)
    {
        if ($bool) {
            $this->modifiers |= self::MODIFIER_PRIVATE;
        } else {
            $this->modifiers &= ~self::MODIFIER_PRIVATE;
        }
        return $this;
    }

    /**
     * @return bitmap
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * Sets all modifiers for this object
     *
     * previous set modifiers will be overwritten
     * @param bitmap $modifiers a combination of the MODIFIER_* constants
     */
    public function setModifiers($modifiers)
    {
        $this->modifiers = $modifiers;
        return $this;
    }
}
