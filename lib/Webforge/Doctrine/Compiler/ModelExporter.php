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
    $export = clone $entity->getDefinition();

    $export->singular = $entity->getSingular();
    $export->plural = $entity->getPlural();
    $export->tableName = $entity->getTableName();

    return $export;
  }
}
