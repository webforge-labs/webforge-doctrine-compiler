<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;

class ModelExporter {

  protected $model;

  /**
   * Returns an expanded, scalar version of the model 
   * 
   * expanded in the way that entities have additional informations that are implcit included in the model.json where the model is read from
   * @param Model $model must be validated from the ModelValidator
   * @return stdClass
   */
  public function exportModel(Model $model) {
    $export = new stdClass;
    $export->namespace = $model->getNamespace();

    $export->entities = array();
    foreach ($model->getEntities() as $entity) {
      $export->entities[] = $this->exportEntity($entity, $model);
    }

    return $export;
  }

}
