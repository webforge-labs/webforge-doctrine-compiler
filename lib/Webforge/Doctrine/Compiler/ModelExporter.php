<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;

/**
 * 
 * this class would look much more constistent if:
 * 
 * the generator would generate a "GeneratedModel" from the normal Model, with generatedEntities as every entity
 * Because in the generate() method from Generator we do a lot of model-expanding stuff that has nothing todo with generation itself (or maybe named wrong)
 */
class ModelExporter {

  /**
   * Returns an expanded, scalar version of the model 
   * 
   * expanded in the way that entities have additional informations that are implcit included in the model.json where the model is read from
   * @param stdClass $model the model that was expanded with the given $generator
   * @param EntityGenerator $generator needs to match the $model
   * @return stdClass
   */
  public function exportModel(Model $model, EntityGenerator $generator) {
    $export = new stdClass;
    $export->namespace = $model->getNamespace();

    $export->entities = array();
    foreach ($generator->getEntities() as $entity) {
      $export->entities[] = $this->exportEntity($entity, $model);
    }

    return $export;
  }

  protected function exportEntity(GeneratedEntity $entity, Model $model) {
    $export = new stdClass;
    $definition = $entity->getDefinition();

    $export->name = $definition->name;
    $export->fqn = $entity->getFQN();
    $export->singular = $entity->getSingular();
    $export->plural = $entity->getPlural();
    $export->tableName = $entity->getTableName();
    $export->extends = $entity->getParent() ? $entity->getParentClass()->getFQN() : NULL;
    $export->description = $entity->getDescription();

    $export->properties = $this->exportProperties($entity, $model);

    return $export;
  }

  protected function exportProperties(GeneratedEntity $entity, Model $model) {
    $properties = array();

    foreach ($entity->getProperties() as $property) {
      $export = new stdClass;
      $export->name = $property->getName();

      if ($property->hasReference()) {
        $export->type = $property->getReferencedEntity()->getFQN();
        $export->isCollection = $property->isEntityCollection();
      } else {
        $export->type = $property->getDefinition()->type->getName();
      }

      $properties[ $property->getName() ] = $export;
    }

    return (object) $properties;
  }
}
