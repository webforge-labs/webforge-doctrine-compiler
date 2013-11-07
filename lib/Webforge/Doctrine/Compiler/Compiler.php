<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Common\System\Dir;
use Webforge\Common\ClassUtil;
use Webforge\Code\Generator\GClass;
use Webforge\Code\Generator\ClassWriter;

class Compiler {

  protected $flags;
  protected $model;

  protected $dir;
  protected $classWriter;

  const PLAIN_ENTITIES = 0x000001;
  const COMPILED_ENTITIES = 0x000002;

  public function __construct(ClassWriter $classWriter) {
    $this->classWriter = $classWriter;
  }

  public function compileModel(stdClass $model, Dir $target, $flags) {
    $this->flags = $flags;
    $this->dir = $target;
    $this->model = $this->validateModel($model);

    foreach ($this->model->entities as $entity) {
      list($gClass, $entityFile) = $this->compileEntity($entity);
      $entityFile->getDirectory()->create();
      $this->classWriter->write($gClass, $entityFile);
    }
  }

  protected function compileEntity(stdClass $entity) {
    $writeFQN = $this->getWriteFQN($entity);

    $gClass = new GClass($writeFQN);

    return array($gClass, $this->mapToFile($writeFQN));
  }

  /**
   * @return Webforge\Common\System\File
   */
  protected function mapToFile($fqn) {
    $url = str_replace('\\', '/', $fqn).'.php';

    return $this->dir->getFile($url);
  }

  protected function getWriteFQN(stdClass $entity) {
    return ClassUtil::expandNamespace($entity->name, $this->model->namespace);
  }

  public function validateModel(stdClass $model) {
    if (!isset($model->namespace) || empty($model->namespace)) {
      throw new InvalidModelException('The .namespace cannot be empty');
    }

    if (!isset($model->entities) || !is_array($model->entities)) {
      throw new InvalidModelException('The .entities have to be an array');
    }

    return $model;
  }
}
