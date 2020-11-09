<?php

namespace Webforge\Code\Generator;

class GMethod extends GModifiersObject
{
   public const APPEND = GObjectCollection::END;
    public const END = GObjectCollection::END;
    public const PREPEND = 0;

    /**
     * @var \Webforge\Code\Generate\GObjectCollection
     */
    protected $parameters;

    /**
     * @var string
     */
    protected $name;

    /**
     * The code from the method
     *
     * @var \Webforge\Code\Generator\GFunctionBody
     */
    protected $body = null;

    /**
     * @var bool
     */
    protected $returnsReference;

    /**
     * @var \Webforge\Code\Generator\GClass
     */
    protected $gClass;

    /**
     * @var string|null
     */
    protected $returnTypeHint;

    /**
     * @param string $name name of the method
     * @param GParameter[] $parameters
     * @param GFunctionBody body
     * @param string|string[] $modifiers
     */
    public function __construct(
        $name = null,
        array $parameters = array(),
        GFunctionBody $body = null,
        $modifiers = self::MODIFIER_PUBLIC
    ) {
        $this->name = $name;
        $this->modifiers = $modifiers;

        $this->parameters = new GObjectCollection(array());
        foreach ($parameters as $parameter) {
            $this->addParameter($parameter);
        }

        if (isset($body)) {
            $this->setBody($body);
        }
    }

    public static function create(
        $name = null,
        array $parameters = array(),
        GFunctionBody $body = null,
        $modifiers = self::MODIFIER_PUBLIC
    ) {
        return new static($name, $parameters, $body, $modifiers);
    }

    /**
     * @chainable
     * @param int $position 0-based
     */
    public function addParameter(GParameter $parameter, $position = self::END)
    {
        $this->parameters->add($parameter, $position);
        return $this;
    }

    /**
     * @param GParam|string $nameOrParameter
     * @chainable
     */
    public function removeParameter($nameOrParameter)
    {
        $this->parameters->remove($nameOrParameter);
        return $this;
    }

    /**
     * @param GParam|string $nameOrParameter
     * @return bool
     */
    public function hasParameter($nameOrParameter)
    {
        return $this->parameters->has($nameOrParameter);
    }

    /**
     * @param int $order 0-based or self::END
     */
    public function setParameterOrder($nameOrParameter, $order)
    {
        $this->parameters->setOrder($nameOrParameter, $order);
        return $this;
    }

    /**
     * @return Webforge\Code\Generator\GParameter[]
     */
    public function getParameters()
    {
        return $this->parameters->toArray();
    }

    /**
     * @return Webforge\Code\Generator\GParameter
     */
    public function getParameterByIndex($index)
    {
        return $this->parameters->get($index);
    }

    /**
     * @return Webforge\Code\Generator\GParameter
     */
    public function getParameterByName($name)
    {
        return $this->parameters->get($name);
    }

    /**
     * @return Webforge\Code\Generator\GParameter
     */
    public function getParameter($nameOrIndex)
    {
        return $this->parameters->get($nameOrIndex);
    }

    /**
     * @return bool
     */
    public function returnsReference()
    {
        return $this->returnsReference;
    }

    /**
     * @chainable
     */
    public function setReturnsReference($bool)
    {
        $this->returnsReference = (bool)$bool;
        return $this;
    }

    /**
     * @return Webforge\Code\Generator\GFunctionBody
     */
    public function getBody()
    {
        return $this->body;
    }


    ///**
    // * @param string $cbraceComment
    // * @chainable
    // */
    //public function setCbraceComment($cbraceComment) {
    //  $this->cbraceComment = $cbraceComment;
    //  return $this;
    //}
    //
    ///**
    // * Der CBrace Comment kann an der öffnenden klammer { der function sein bevor das EOL kommt
    // *
    // * @return string
    // */
    //public function getCbraceComment() {
    //  return $this->cbraceComment;
    //}

    /**
     *
     * the return value of this function is not reliable if gClass is not set
     * @return bool
     */
    public function isInInterface()
    {
        return isset($this->gClass) && $this->gClass->isInterface();
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->name;
    }

    /**
     * @param Webforge\Code\Generator\GClass $gClass
     * @chainable
     */
    public function setGClass(GClass $gClass)
    {
        $this->gClass = $gClass;
        return $this;
    }

    /**
     * @return Webforge\Code\Generator\GClass
     */
    public function getGClass()
    {
        return $this->gClass;
    }

    // @codeCoverageIgnoreStart

    /**
     * @chainable
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @chainable
     */
    public function setBody(GFunctionBody $body)
    {
        $this->body = $body;
        return $this;
    }
    // @codeCoverageIgnoreEnd

    /**
     * @return string|null
     */
    public function getReturnTypeHint(): ?string
    {
        return $this->returnTypeHint;
    }

    /**
     * @param string $returnTypeHint
     */
    public function setReturnTypeHint(string $returnTypeHint): void
    {
        $this->returnTypeHint = $returnTypeHint;
    }
}
