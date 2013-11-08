<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Common\System\Dir;
use Webforge\Common\ClassUtil;
use Webforge\Code\Generator\ClassWriter;
use Webforge\Code\Generator\DocBlock;
use Webforge\Code\Generator\GClass;

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

    foreach ($this->model->getEntities() as $entity) {
      list($gClass, $entityFile) = $this->compileEntity($entity);
      //print "compiled entity ".$entity->name.' to '.$entityFile."\n";
    }
  }

  protected function compileEntity(stdClass $entity) {
    $entityFQN = ClassUtil::expandNamespace($entity->name, $this->model->getNamespace());
    $compiledEntityFile = NULL;

    $gClass = $this->entityGenerator->generate($entity, $entityFQN, $this->model);

    if ($this->flags & self::COMPILED_ENTITIES) {
      // we split up the gclass into Compiled$entityName and $entityName class
      // the $entityName class needs the docblock from the "real" class because this is what doctrine sees
      $compiledClass = $gClass;
      $entityClass = clone $gClass;

      $compiledClass->setName('Compiled'.$entityClass->getName());
      $compiledClass->setAbstract(TRUE);

      // entity extends Generation-Gap Entity
      $entityClass->setParent($compiledClass);
      
      // move docblock
      $entityClass->setDocBlock(clone $compiledClass->getDocBlock());
      $compiledClass->setDocBlock(new DocBlock('Compiled Entity for '.$entityClass->getFQN()."\n\nTo change table name or entity repository edit the ".$entityClass->getFQN().' class.'));

      // write both
      $entityFile = $this->write($entityClass);
      $compiledEntityFile = $this->write($compiledClass);

    } else {
      $entityFile = $this->write($gClass);
    }

    return array($gClass, $entityFile, $compiledEntityFile);
  }

  protected function write(GClass $gClass) {
    $entityFile = $this->mapToFile($gClass->getFQN());
    $entityFile->getDirectory()->create();

    $this->classWriter->write($gClass, $entityFile);

    return $entityFile;
  }

  /**
   * @return Webforge\Common\System\File
   */
  protected function mapToFile($fqn) {
    $url = str_replace('\\', '/', $fqn).'.php';

    return $this->dir->getFile($url);
  }
}
