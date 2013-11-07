<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Common\System\Dir;
use Webforge\Common\ClassUtil;
use Webforge\Code\Generator\ClassWriter;

class Compiler {

  protected $flags;
  protected $model;
  protected $validator;

  protected $dir;
  protected $classWriter;

  const PLAIN_ENTITIES = 0x000001;
  const COMPILED_ENTITIES = 0x000002;

  public function __construct(ClassWriter $classWriter, EntityGenerator $entityGenerator, ModelValidator $validator) {
    $this->classWriter = $classWriter;
    $this->validator = $validator;
    $this->entityGenerator = $entityGenerator;
  }

  public function compileModel(stdClass $model, Dir $target, $flags) {
    $this->flags = $flags;
    $this->dir = $target;
    $this->model = $this->validator->validateModel($model);

    foreach ($this->model->entities as $entity) {
      list($gClass, $entityFile) = $this->compileEntity($entity);
    }
  }

  protected function compileEntity(stdClass $entity) {
    $writeFQN = $this->getWriteFQN($entity);

    $gClass = $this->entityGenerator->generate($entity, $writeFQN);

    $entityFile = $this->mapToFile($writeFQN);
    $entityFile->getDirectory()->create();

    $this->classWriter->write($gClass, $entityFile);

    return array($gClass, $entityFile);
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


}
