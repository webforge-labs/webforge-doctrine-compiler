<?php

namespace Webforge\Doctrine\Compiler;

use LogicException;
use stdClass;
use Webforge\Code\Generator\GClass;
use Webforge\Code\Generator\GProperty;
use Doctrine\ORM\Mapping as ORM;
use Webforge\Doctrine\Annotations\Writer as AnnotationsWriter;
use Webforge\Types\IdType;
use Webforge\Types\DoctrineExportableType;
use JMS\Serializer\Annotation as SA;

class EntityMappingGenerator {

  protected $entity;
  protected $annotationsWriter;
  protected $model;

  public function __construct(AnnotationsWriter $annotationsWriter) {
    $this->annotationsWriter = $annotationsWriter;
    $this->annotationsWriter->setAnnotationNamespaceAlias('Doctrine\ORM\Mapping', 'ORM');
  }

  public function init(GeneratedEntity $entity, Model $model) {
    $this->entity = $entity;
    $this->model = $model;
  }

  public function annotate() {
    if (!isset($this->entity)) {
      throw new LogicException('Call init() before annotate');
    }

    $this->annotateClass($this->entity->gClass, $this->entity);

    foreach ($this->entity->getProperties() as $property) {
      $this->annotateProperty($property, $this->entity);
    }
  }

  protected function annotateClass($gClass, GeneratedEntity $entity) {
    $gClass->addImport(new GClass('Doctrine\ORM\Mapping'), 'ORM');
    $gClass->addImport(new GClass('JMS\Serializer\Annotation'), 'Serializer');
    $gClass->setDocBlock(
      $this->createDocBlock(
        $entity->getDescription()."\n\n".'this entity was compiled from '.__NAMESPACE__,
        $this->generateClassAnnotations($entity)
      )
    );
  }

  protected function generateClassAnnotations(GeneratedEntity $entity) {
    $annotations = array();
    $annotations[] = $this->generateEntityAnnotation($entity);
    $annotations[] = $this->generateTableAnnotation($entity);
    $annotations = array_merge($annotations, $this->generateSerializerAnnotations($entity));

    return $annotations;
  }

  protected function generateSerializerAnnotations(GeneratedEntity $entity) {
    return array(
      '@Serializer\ExclusionPolicy("all")'
    );
  }

  protected function annotateProperty(GeneratedProperty $property, GeneratedEntity $entity) {
    $property->setDocBlock(
      $this->createDocBlock(
        isset($definition->description) ? $definition->description : $property->getName(),
        $this->generatePropertyAnnotations($property, $entity)
      )
    );
  }

  protected function generatePropertyAnnotations(GeneratedProperty $property, GeneratedEntity $entity) {
    $annotations = array();
    $type = $property->getType();

    $annotations[] = new SA\Expose();

    if ($property->hasReference()) {
      $associationPair = $this->model->getAssociationFor($entity, $property);
      $annotations = array_merge($annotations, $this->generateAssociationAnnotation($property, $entity, $associationPair));

    } elseif ($type instanceof IdType) {
      $annotations[] = new ORM\Id();

      $column = new ORM\Column();
      $column->type = $type instanceof DoctrineExportableType ? $type->getDoctrineExportType() : 'integer';
      $annotations[] = $column;

      $generatedValue = new ORM\GeneratedValue();
      $generatedValue->strategy = 'AUTO';
      $annotations[] = $generatedValue;

      $annotations[] = sprintf('@Serializer\Type("%s")', $column->type);

    } elseif ($type instanceof DoctrineExportableType) {
      $column = new ORM\Column();
      $column->type = $type->getDoctrineExportType();

      if (isset($property->getDefinition()->length)) {
        $column->length = $property->getDefinition()->length;
      }

      $column->nullable = $property->isNullable();

      $annotations[] = $column;

      // http://jmsyst.com/libs/serializer/master/reference/annotations#type
      $annotations[] = sprintf('@Serializer\Type("%s")', $column->type);
    }

    return $annotations;
  }

  protected function generateAssociationAnnotation(GeneratedProperty $property, GeneratedEntity $entity, stdClass $associationPair) {
    $annotations = array();

    $association = $entity->equals($associationPair->owning->entity) ? $associationPair->owning : $associationPair->inverse;
    $hasInverse = isset($associationPair->inverse);

    if ($association->isOneToMany()) {
      $annotation = new ORM\OneToMany();

      $annotation->targetEntity = $association->referencedEntity->getFQN();
      // the many side is always existing so the referencedProperty is defined
      $annotation->mappedBy = $association->referencedProperty->getName();

      // @TODO cascade

      $annotations[] = $annotation;

      $annotations[] = sprintf('@Serializer\Type("ArrayCollection")', $association->referencedEntity->getFQN());

    } elseif ($association->isManyToOne()) {
      // we are always the owning side

      $annotation = new ORM\ManyToOne();
      $annotation->targetEntity = $association->referencedEntity->getFQN();

      if ($hasInverse) {
        $annotation->inversedBy = $association->referencedProperty->getName();
      }

      // @TODO cascade
      $annotations[] = $annotation;

      // serializer
      $annotations[] = sprintf('@Serializer\Type("%s")', $association->referencedEntity->getFQN());

    } elseif ($association->isManyToMany()) {
      $annotation = new ORM\ManyToMany();

      $annotation->targetEntity = $association->referencedEntity->getFQN();

      if (!$association->isOwning()) {
        $annotation->mappedBy = $association->referencedProperty->getName();
      } elseif ($hasInverse) {
        $annotation->inversedBy = $association->referencedProperty->getName();
      }

      $annotations[] = $annotation;

      $annotations[] = sprintf('@Serializer\Type("ArrayCollection")', $association->referencedEntity->getFQN());

      // we need a table for manyToMany
      $table = new ORM\JoinTable();
      $table->name = sprintf('%s2%s', $associationPair->owning->entity->getTableName(), $associationPair->owning->referencedEntity->getTableName());

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
      $joinColumn->name = sprintf('%s_%s', $associationPair->owning->entity->getTableName(), $associationPair->owning->entity->getIdentifierColumn());
      $joinColumn->onDelete = 'cascade';

      $table->joinColumns = array($joinColumn);

      // inverse join column has to be existing no matter if associations reverse is existing
      $inverseJoinColumn  = new ORM\JoinColumn();
      $inverseJoinColumn->name = sprintf('%s_%s', $associationPair->owning->referencedEntity->getTableName(), $associationPair->owning->referencedEntity->getIdentifierColumn());
      $inverseJoinColumn->onDelete = 'cascade';

      $table->inverseJoinColumns = array($inverseJoinColumn);
      
      $annotations[] = $table;

    } elseif ($association->isOneToOne()) {
      throw new \Webforge\Common\Exception\NotImplementedException('OneToOne not needed right now: '.$association->getUniqueSlug());

      $annotations[] = sprintf('@Serializer\Type("%s")', $association->referencedEntity->getFQN());
    }

    return $annotations;
  }

  protected function generateEntityAnnotation(GeneratedEntity $entity) {
    return new ORM\Entity();
  }

  protected function generateTableAnnotation(GeneratedEntity $entity) {
    $table = new ORM\Table();
    $table->name = $entity->getTableName();

    return $table;
  }

  protected function createDocBlock($description, $annotations) {
    return new AnnotationsDocBlock($description, $annotations, $this->annotationsWriter);
  }
}
