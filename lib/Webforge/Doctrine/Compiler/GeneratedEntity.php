<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Code\Generator\GClass;

class GeneratedEntity extends DefinitionPart
{
    public $gClass;

    protected $parent;
    protected $properties = array();

    protected $tableName;
    protected $singular;
    protected $plural;

    public function __construct(stdClass $definition, GClass $gClass)
    {
        parent::__construct($definition);
        $this->gClass = $gClass;
    }

    public function inflect(Inflector $inflector)
    {
        $this->tableName = $inflector->tableName($this->definition);
        $this->singular = $inflector->singularSlug($this->definition);
        $this->plural = $inflector->pluralSlug($this->definition);
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
        $gClass = $parent instanceof GeneratedEntity ? $parent->gClass : $parent;
        $this->gClass->setParent($gClass);
    }

    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return Webforge\Common\ClassInterface
     */
    public function getParentClass()
    {
        return $this->gClass->getParent();
    }

    public function getProperty($name)
    {
        return $this->properties[$name];
    }

    public function hasProperty($name)
    {
        return array_key_exists($name, $this->properties);
    }

    // be sure to connnect with gClass yourself
    public function addProperty(GeneratedProperty $property)
    {
        $this->properties[$property->getName()] = $property;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getGClass()
    {
        return $this->gClass;
    }

    public function getFQN()
    {
        return $this->gClass->getFQN();
    }

    public function getName()
    {
        return $this->gClass->getName();
    }

    public function equals(GeneratedEntity $otherEntity)
    {
        return $otherEntity->getFQN() === $this->getFQN();
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getIdentifierColumn()
    {
        return isset($this->definition->identifier) ? $this->definition->identifier : 'id';
    }

    public function getDescription()
    {
        return isset($this->definition->description) ? $this->definition->description : null;
    }

    /**
     * Returns a Slug for the entity name (+ subnamespace) with plural ending
     *
     * is used as restful-url part for example
     * @return string
     */
    public function getPlural()
    {
        return $this->plural;
    }

    /**
     * Returns a Slug for the entity name (+ subnamespace) with singular ending
     *
     * is used as restful-url part for example
     * @return string
     */
    public function getSingular()
    {
        return $this->singular;
    }

    /**
     * Returns the prefix in joinColumns and such
     *
     * its the table name in singujlar often
     * @return string
     */
    public function getColumnPrefix()
    {
        return str_replace('-', '', $this->singular);
    }
}
