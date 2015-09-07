<?php

namespace Webforge\Doctrine\Compiler\Extensions;

use Webforge\Doctrine\Compiler\GeneratedProperty;
use Webforge\Doctrine\Compiler\GeneratedEntity;
use Webforge\Doctrine\Compiler\ModelAssociation;
use Webforge\Code\Generator\GClass;
use Webforge\Code\Generator\GMethod;
use Webforge\Code\Generator\GFunctionBody;
use Webforge\Code\Generator\GParameter;
use Webforge\Types\CodeExporter;
use Webforge\Common\CodeWriter;
use Webforge\Doctrine\Compiler\InvalidModelException;

class CMSEntities implements Extension {

  public function __construct() {
    $this->codeExporter = new CodeExporter(new CodeWriter());
  }


  public function onClassGeneration(GeneratedEntity $entity, GClass $gClass) {
    $this->buildGetIdentifier($entity, $gClass);
    $this->buildSetIdentifier($entity, $gClass);
    $this->buildGetEntityName($entity, $gClass);
    $this->buildSetMetaGetter($entity, $gClass);
  }

  protected function buildGetIdentifier(GeneratedEntity $entity, GClass $gClass) {
    $getter = $gClass->createMethod(
      'getIdentifier',
      array(),
      GFunctionBody::create(array(
        'return $this->id;'
      )),
      GMethod::MODIFIER_PUBLIC
    );

    $gClass->setMethodOrder($getter, 2);
  }

  protected function buildSetIdentifier(GeneratedEntity $entity, GClass $gClass) {
    $setter = $gClass->createMethod(
      'setIdentifier',
      array($param = $entity->getProperty('id')->getParameter()),
      GFunctionBody::create(array(
        sprintf('$this->id = $%s;', $param->getName()),
        'return $this;'
      )),
      GMethod::MODIFIER_PUBLIC
    );

    $gClass->setMethodOrder($setter, 3);
  }

  protected function buildGetEntityName(GeneratedEntity $entity, GClass $gClass) {
    return $gClass->createMethod(
      'getEntityName',
      array(),
      GFunctionBody::create(array(
        sprintf("return '%s';", $entity->getFQN())
      )),
      GMethod::MODIFIER_PUBLIC
    );
  }

  /**
   * Creates a getter called 'getSetMeta' that returns the type info for all Properties of the entity
   */
  protected function buildSetMetaGetter(GeneratedEntity $entity, GClass $gClass) {
    $code = array();
    
    $code[] = 'return new \Psc\Data\SetMeta(array(';
    foreach ($entity->getProperties() as $property) {

      if ($property->getType() === NULL) {
        throw new InvalidModelException('Property '.$property->getName().' hat keinen Typ!');
      }
      
      $code[] = sprintf("  '%s' => %s,", $property->getName(), $this->codeExporter->exportType($property->getType()));
    }
    $code[] = "));";

    $gMethod = $gClass->createMethod(
      'getSetMeta',
      array(),
      GFunctionBody::create(
        $code
      ),
      GMethod::MODIFIER_STATIC | GMethod::MODIFIER_PUBLIC
    );
    
    return $gMethod;
  }

  public function onPropertyAnnotationsGeneration(array &$annotations, GeneratedProperty $property, GeneratedEntity $entity) {
  }

  public function onClassAnnotationsGeneration(array &$annotations, GeneratedEntity $entity) {
  }

  public function onAssociationAnnotationsGeneration(array &$annotations, ModelAssociation $association, \stdClass $associationPair, GeneratedProperty $property, GeneratedEntity $entity) {
  }
}
