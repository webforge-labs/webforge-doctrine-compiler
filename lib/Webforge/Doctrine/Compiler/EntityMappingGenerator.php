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

class EntityMappingGenerator {

  protected $gClass;
  protected $entity;
  protected $inflector;
  protected $annotationsWriter;

  public function __construct(AnnotationsWriter $annotationsWriter, Inflector $inflector) {
    $this->annotationsWriter = $annotationsWriter;
    $this->annotationsWriter->setAnnotationNamespaceAlias('Doctrine\ORM\Mapping', 'ORM');

    $this->inflector = $inflector;
  }

  public function init(stdClass $entity) {
    $this->entity = $entity;
  }

  public function annotate(GClass $gClass) {
    if (!isset($this->entity)) {
      throw new LogicException('Call init() before annotate');
    }

    $this->annotateClass($gClass, $this->entity);

    foreach ($gClass->getProperties() as $property) {
      $this->annotateProperty($property, $gClass, $this->entity);
    }
  }

  protected function annotateClass($gClass, $entity) {
    $gClass->addImport(new GClass('Doctrine\ORM\Mapping'), 'ORM');
    $gClass->setDocBlock(
      $this->createDocBlock(
        "\n\n".'this entity was compiled from '.__NAMESPACE__,
        array(
          $this->generateEntityAnnotation($entity),
          $this->generateTableAnnotation($entity)
        )
      )
    );
  }

  protected function annotateProperty(GProperty $property, GClass $gClass, stdClass $entity) {
    // run over properties or entity->properties?
    $definition = $entity->properties->{$property->getName()};

    $property->setDocBlock(
      $this->createDocBlock(
        isset($definition->description) ? $definition->description : $property->getName(),
        $this->generatePropertyAnnotations($property, $definition, $gClass)
      )
    );
  }

  protected function generatePropertyAnnotations(GProperty $property, stdClass $definition, GClass $gClass) {
    $annotations = array();
    $type = $property->getType();

    if ($type instanceof IdType) {
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
    }

    return $annotations;
  }

  protected function generateEntityAnnotation(stdClass $entity) {
    return new ORM\Entity();
  }

  protected function generateTableAnnotation(stdClass $entity) {
    $table = new ORM\Table();
    $table->name = $this->inflector->tableName($entity);

    return $table;
  }

  protected function createDocBlock($description, $annotations) {
    return new AnnotationsDocBlock($description, $annotations, $this->annotationsWriter);
  }
}
