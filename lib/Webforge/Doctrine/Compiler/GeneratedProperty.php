<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Code\Generator\GParameter;
use Webforge\Code\Generator\GProperty;
use Webforge\Types\InterfacedType;

class GeneratedProperty extends DefinitionPart
{
    protected $name;

    protected $gProperty;

    protected $setterName;
    protected $getterName;
    protected $parameter;
    protected $collectionNames;

    protected $relationName;
    protected $relationType;

    public function __construct(stdClass $definition, GProperty $property)
    {
        parent::__construct($definition);
        $this->gProperty = $property;
        $this->name = $property->getName();
    }

    public function inflect(Inflector $inflector)
    {
        $this->setterName = $inflector->getPropertySetterName($this->gProperty, $this->definition);
        $this->getterName = $inflector->getPropertyGetterName($this->gProperty, $this->definition);

        // note: this is the parameter for the setter but not its not 100% identical with the constructor parameter
        // the constructor parameter might have another default value (see in EntityGenerator)
        $this->parameter = new GParameter(
            $this->getName(),
            $this->gProperty->getType(),
            $this->isNullable() ? null : GParameter::UNDEFINED
        );

        $this->collectionNames['add'] = $inflector->getCollectionAdderName($this->gProperty, $this->definition);
        $this->collectionNames['remove'] = $inflector->getCollectionRemoverName($this->gProperty, $this->definition);
        $this->collectionNames['has'] = $inflector->getCollectionCheckerName($this->gProperty, $this->definition);

        if ($this->hasDefinitionOf('relation')) {
            if (in_array($this->definition->relation, array('OneToOne', 'ManyToOne'))) {
                $this->relationType = $this->definition->relation;
            } else {
                $this->relationName = $this->definition->relation;
            }
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParameter()
    {
        return $this->parameter;
    }

    public function getCollectionDoerName($type)
    {
        return $this->collectionNames[$type];
    }

    public function getGProperty()
    {
        return $this->gProperty;
    }

    public function getSetterName()
    {
        return $this->setterName;
    }

    public function getGetterName()
    {
        return $this->getterName;
    }

    public function getReferencedEntity()
    {
        return $this->definition->referencedEntity;
    }

    public function isEntityCollection()
    {
        return isset($this->definition->reference) && $this->definition->reference instanceof EntityCollectionReference;
    }

    public function isEntity()
    {
        return isset($this->definition->reference) && $this->definition->reference instanceof EntityReference && !$this->definition->reference instanceof EntityCollectionReference;
    }

    public function hasReference()
    {
        return isset($this->definition->reference);
    }

    public function getRelationName()
    {
        return $this->relationName;
    }

    public function getRelationType()
    {
        return $this->relationType;
    }

    public function hasRelationName()
    {
        return isset($this->relationName);
    }

    public function isNullable()
    {
        return isset($this->definition->nullable) ? $this->definition->nullable : false;
    }

    public function getRelationCascade()
    {
        return isset($this->definition->cascade) ? (array)$this->definition->cascade : null;
    }

    // the delete on db level in the joinColumn
    public function getOnDelete()
    {
        return isset($this->definition->onDelete) ? $this->definition->onDelete : null;
    }

    public function hasOnDelete()
    {
        return $this->hasDefinitionOf('onDelete');
    }

    public function hasJoinTableName()
    {
        return $this->hasDefinitionOf('joinTableName');
    }

    public function hasOrderBy()
    {
        return $this->hasDefinitionOf('orderBy');
    }

    public function hasDefaultValue()
    {
        return $this->hasDefinitionOf('defaultValue');
    }

    public function getDefaultValue()
    {
        return $this->requireDefinitionOf('defaultValue');
    }

    public function getOrderBy()
    {
        return $this->requireDefinitionOf('orderBy');
    }

    public function getJoinTableName()
    {
        return isset($this->definition->joinTableName) && !empty($this->definition->joinTableName) ? $this->definition->joinTableName : null;
    }

    /**
     * Returns the php Documentor type for (at)param or (at)return annotations
     *
     * @return string
     */
    public function getDocType()
    {
        $propertyType = $this->gProperty->getType();
        if ($propertyType instanceof InterfacedType) {
            return '\\' . ltrim($propertyType->getDocType(), '\\');
        } else {
            return $propertyType->getDocType() ?: 'mixed';
        }
    }

    public function getType()
    {
        return $this->gProperty->getType();
    }

    public function setDocBlock($docBlock)
    {
        $this->gProperty->setDocBlock($docBlock);
    }
}
