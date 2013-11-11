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

  public function init(GeneratedEntity $entity) {
    $this->entity = $entity;
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

  protected function generateEntityAnnotation(GeneratedEntity $entity) {
    return new ORM\Entity();
  }

  protected function generateTableAnnotation(GeneratedEntity $entity) {
    $table = new ORM\Table();
    // @TODO implement inflect() in Entity and create a getter
    $table->name = $this->inflector->tableName($entity->definition);

    return $table;
  }

  protected function createDocBlock($description, $annotations) {
    return new AnnotationsDocBlock($description, $annotations, $this->annotationsWriter);
  }
}
