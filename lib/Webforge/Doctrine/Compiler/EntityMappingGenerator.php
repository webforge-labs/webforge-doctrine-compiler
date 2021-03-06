<?php

namespace Webforge\Doctrine\Compiler;

use Doctrine\ORM\Mapping as ORM;
use LogicException;
use stdClass;
use Webforge\Code\Generator\GClass;
use Webforge\Common\Exception\NotImplementedException;
use Webforge\Doctrine\Annotations\Writer as AnnotationsWriter;
use Webforge\Types\DoctrineExportableType;
use Webforge\Types\IdType;

class EntityMappingGenerator
{
    protected $entity;
    protected $annotationsWriter;
    protected $model;

    protected $usedTables;

    public function __construct(AnnotationsWriter $annotationsWriter, array $extensions)
    {
        $this->annotationsWriter = $annotationsWriter;
        $this->annotationsWriter->setAnnotationNamespaceAlias('Doctrine\ORM\Mapping', 'ORM');
        $this->extensions = $extensions;
        $this->usedTables = array();
    }

    public function init(GeneratedEntity $entity, Model $model)
    {
        $this->entity = $entity;
        $this->model = $model;
    }

    public function annotate()
    {
        if (!isset($this->entity)) {
            throw new LogicException('Call init() before annotate');
        }

        $this->annotateClass($this->entity->gClass, $this->entity);

        foreach ($this->entity->getProperties() as $property) {
            $this->annotateProperty($property, $this->entity);
        }
    }

    protected function annotateClass($gClass, GeneratedEntity $entity)
    {
        $gClass->addImport(new GClass('Doctrine\ORM\Mapping'), 'ORM');

        foreach ($this->extensions as $extension) {
            $extension->onClassGeneration($entity, $gClass);
        }

        $gClass->setDocBlock(
            $this->createDocBlock(
                $entity->getDescription() . "\n\n" . 'this entity was compiled from ' . __NAMESPACE__,
                $this->generateClassAnnotations($entity)
            )
        );
    }

    protected function generateClassAnnotations(GeneratedEntity $entity)
    {
        $annotations = array();
        $annotations[] = $this->generateEntityAnnotation($entity);
        $annotations[] = $this->generateTableAnnotation($entity);

        foreach ($this->extensions as $extension) {
            $extension->onClassAnnotationsGeneration($annotations, $entity);
        }

        return $annotations;
    }

    protected function annotateProperty(GeneratedProperty $property, GeneratedEntity $entity)
    {
        $property->setDocBlock(
            $this->createDocBlock(
                (isset($definition->description) ? $definition->description : $property->getName()) . "\n" .
                sprintf('@var %s', $property->getDocType()),
                $this->generatePropertyAnnotations($property, $entity)
            )
        );
    }

    protected function generatePropertyAnnotations(GeneratedProperty $property, GeneratedEntity $entity)
    {
        $annotations = array();
        $type = $property->getType();

        if ($property->hasReference()) {
            $associationPair = $this->model->getAssociationFor($entity, $property);
            $annotations = array_merge(
                $annotations,
                $this->generateAssociationAnnotation($property, $entity, $associationPair)
            );
        } elseif ($type instanceof IdType) {
            $annotations[] = new ORM\Id();

            $column = new ORM\Column();
            $column->type = $type instanceof DoctrineExportableType ? $type->getDoctrineExportType() : 'integer';
            $annotations[] = $column;

            $generatedValue = new ORM\GeneratedValue();
            $generatedValue->strategy = 'AUTO';
            $annotations[] = $generatedValue;
        } elseif ($type instanceof DoctrineExportableType) {
            $column = new ORM\Column();
            $column->type = $type->getDoctrineExportType();

            if (isset($property->getDefinition()->length)) {
                $column->length = $property->getDefinition()->length;
            }
            if (isset($property->getDefinition()->precision)) {
                $column->precision = $property->getDefinition()->precision;
            }
            if (isset($property->getDefinition()->scale)) {
                $column->scale = $property->getDefinition()->scale;
            }

            $column->nullable = $property->isNullable();

            $annotations[] = $column;
        }

        foreach ($this->extensions as $extension) {
            $extension->onPropertyAnnotationsGeneration($annotations, $property, $entity);
        }

        return $annotations;
    }

    protected function generateAssociationAnnotation(
        GeneratedProperty $property,
        GeneratedEntity $entity,
        stdClass $associationPair
    ) {
        $annotations = array();

        $association = $entity->equals(
            $associationPair->owning->entity
        ) && $property === $associationPair->owning->property ? $associationPair->owning : $associationPair->inverse;
        $hasInverse = isset($associationPair->inverse) && !$association->isSelfReferencingUnidirectional();

        if ($association->isOneToMany()) {
            $annotation = new ORM\OneToMany();

            $annotation->targetEntity = $association->referencedEntity->getFQN();
            // the many side is always existing so the referencedProperty is defined
            $annotation->mappedBy = $association->referencedProperty->getName();

            $annotation->cascade = $property->getRelationCascade();

            $annotations[] = $annotation;
        } elseif ($association->isManyToOne()) {
            // we are always the owning side

            $annotation = new ORM\ManyToOne();
            $annotation->targetEntity = $association->referencedEntity->getFQN();

            if ($hasInverse) {
                $annotation->inversedBy = $association->referencedProperty->getName();
            }

            $annotation->cascade = $property->getRelationCascade();

            $annotations[] = $annotation;

            if (
                $property->hasOnDelete() || $property->hasDefinitionOf(
                    'nullable'
                ) || $associationPair->owning->entity->getIdentifierColumn() != 'id'
            ) {
                $annotations[] = $joinColumn = new ORM\JoinColumn();
                $joinColumn->onDelete = $property->getOnDelete();

                if ($associationPair->owning->entity->getIdentifierColumn() != 'id') {
                    $joinColumn->referencedColumnName = $associationPair->owning->entity->getIdentifierColumn();
                }

                $nullable = true;
                if (
                    $property->hasDefinitionOf(
                        'nullable',
                        $nullable
                    )
                ) { // dont use isNullable because this defaults to FALSE but joinColumn->nullable defaults to TRUE
                    $joinColumn->nullable = $nullable;
                }
            }
        } elseif ($association->isManyToMany()) {
            $annotation = new ORM\ManyToMany();

            $annotation->targetEntity = $association->referencedEntity->getFQN();

            if (!$association->isOwning()) {
                $annotation->mappedBy = $association->referencedProperty->getName();
            } elseif ($hasInverse) {
                $annotation->inversedBy = $association->referencedProperty->getName();
            }

            $annotation->cascade = $property->getRelationCascade();

            $annotations[] = $annotation;

            // we need a table for manyToMany
            $table = new ORM\JoinTable();
            $table->name = $association->getTableName();

            if (array_key_exists($table->name, $this->usedTables)) {
                $conflictAssociation = $this->usedTables[$table->name];

                if (!$conflictAssociation->isEqual($associationPair->owning)) {
                    throw new InvalidModelException(
                        sprintf(
                            "The ManyToMany-relation %s uses the same tablename (%s) as the relation %s.\n" .
                            "To avaid this duplicate table you have to set joinTableName in one of the relations\n",
                            $associationPair->owning->getUniqueSlug(),
                            $table->name,
                            $conflictAssociation->getUniqueSlug()
                        )
                    );
                }
            }

            $this->usedTables[$table->name] = $associationPair->owning;

            /*@ORM\JoinTable(
                name="page2contentstream",
                joinColumns={
                  @ORM\JoinColumn(name="page_id", onDelete="cascade")
                },
                inverseJoinColumns={
                  @ORM\JoinColumn(name="contentstream_id", onDelete="cascade")
                }
              )
            */
            $joinColumn = new ORM\JoinColumn();
            $joinColumn->name = sprintf(
                '%s_%s',
                $associationPair->owning->entity->getColumnPrefix(),
                $associationPair->owning->entity->getIdentifierColumn()
            );
            $joinColumn->onDelete = 'cascade';

            if ($associationPair->owning->entity->getIdentifierColumn() != 'id') {
                $joinColumn->referencedColumnName = $associationPair->owning->entity->getIdentifierColumn();
            }

            $table->joinColumns = array($joinColumn);

            // inverse join column has to be existing no matter if associations reverse is existing
            $inverseJoinColumn = new ORM\JoinColumn();
            $inverseJoinColumn->name = sprintf(
                '%s%s_%s',
                $associationPair->owning->isSelfReferencing() ? 'self_' : '',
                $associationPair->owning->referencedEntity->getColumnPrefix(),
                $associationPair->owning->referencedEntity->getIdentifierColumn()
            );
            $inverseJoinColumn->onDelete = 'cascade';

            if ($associationPair->owning->referencedEntity->getIdentifierColumn() != 'id') {
                $inverseJoinColumn->referencedColumnName = $associationPair->owning->referencedEntity->getIdentifierColumn(
                );
            }

            $table->inverseJoinColumns = array($inverseJoinColumn);

            $annotations[] = $table;
        } elseif ($association->isOneToOne()) {
            throw new NotImplementedException('OneToOne not needed right now: ' . $association->getUniqueSlug());
        }

        if (($association->isOneToMany() || $association->isManyToMany()) && $association->hasOrderBy()) {
            $orderBy = new ORM\OrderBy();
            $orderBy->value = $association->getOrderBy();
            $annotations[] = $orderBy;
        }

        foreach ($this->extensions as $extension) {
            $extension->onAssociationAnnotationsGeneration(
                $annotations,
                $association,
                $associationPair,
                $property,
                $entity
            );
        }


        return $annotations;
    }

    protected function generateEntityAnnotation(GeneratedEntity $entity)
    {
        return new ORM\Entity();
    }

    protected function generateTableAnnotation(GeneratedEntity $entity)
    {
        $table = new ORM\Table();
        $table->name = $entity->getTableName();

        return $table;
    }

    protected function createDocBlock($description, $annotations)
    {
        return new AnnotationsDocBlock($description, $annotations, $this->annotationsWriter);
    }
}
