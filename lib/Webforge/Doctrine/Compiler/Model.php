<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\ClassUtil;

class Model {

  protected $namespace;
  protected $entites;

  public function __construct($namespace, Array $entities) {
    $this->namespace = $namespace;
    $this->indexEntities($entities);
  }

  public function getEntities() {
    return $this->entities;
  }

  public function getNamespace() {
    return $this->namespace;
  }

  public function getEntity($name) {
    return $this->entities[$name];
  }

  public function hasEntity($name) {
    return array_key_exists($name, $this->entities);
  }

  protected function indexEntities(Array $entities) {
    $this->entities = array();
    foreach($entities as $entity) {
      $entity->fqn = ClassUtil::expandNamespace($entity->name, $this->getNamespace());
      $this->entities[$entity->name] = $entity;
    }
  }
}
