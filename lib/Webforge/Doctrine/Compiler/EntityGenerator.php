<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Code\Generator\GClass;
use Webforge\Code\Generator\GProperty;
use Webforge\Code\Generator\GParameter;
use Webforge\Code\Generator\GMethod;
use Webforge\Code\Generator\GFunctionBody;
use Webforge\Types\Type;
use Webforge\Common\String as S;
use InvalidArgumentException;
use Webforge\Types\PersistentCollectionType;
use Webforge\Types\TypeException;
use Webforge\Types\CollectionType;
use Webforge\Types\ObjectType;
use Webforge\Types\EntityType;

class EntityGenerator {

  protected $entity;
  protected $gClass;
  protected $inflector;
  protected $mappingGenerator;
  protected $model;
  protected $gClassBroker;

  public function __construct(Inflector $inflector, EntityMappingGenerator $mappingGenerator) {
    $this->inflector = $inflector;
    $this->mappingGenerator = $mappingGenerator;
  }

  public function generate(stdClass $entity, $fqn, Model $model, GClassBroker $broker) {
    $this->model = $model;
    $this->entity = $entity;
    $this->gClass = new GClass($fqn);
    $this->gClassBroker = $broker;

    if ($entity->extends) {
      $this->gClass->setParent($broker->getElevated($entity->extends->fqn, $fqn));
    }

    $this->generateProperties($this->entity->properties);
    $this->generateAssociationsAPI($this->gClass);
    $this->generateConstructor($this->gClass, $this->entity);

    $this->mappingGenerator->init($entity);
    $this->mappingGenerator->annotate($this->gClass);

    return $this->gClass;
  }

  protected function generateProperties($propertyDefinitions) {
    foreach ($propertyDefinitions as $name => $propertyDefinition) {
      $property = $this->gClass->createProperty(
        $name, 
        $this->parseType($propertyDefinition->type),
        $default = GClass::UNDEFINED,
        $modifiers = GProperty::MODIFIER_PROTECTED
      );

      $this->generateSetter($property, $propertyDefinition);
      $this->generateGetter($property, $propertyDefinition);
    }
  }

  protected function generateSetter(GProperty $property, stdClass $propertyDefinition) {
    $setterName = $this->inflector->getPropertySetterName($property, $propertyDefinition);
    
    $gMethod = $this->gClass->createMethod(
      $setterName,
      array(
        $parameter = new GParameter(
          $property->getName(),
          $property->getType(),
          $propertyDefinition->nullable ? NULL : GParameter::UNDEFINED
        )
      ),
      GFunctionBody::create(
        array(
          sprintf('$this->%s = $%s;', $property->getName(), $parameter->getName()),
          'return $this;'
        )
      ),
      GMethod::MODIFIER_PUBLIC
    );
    
    $gMethod->createDocBlock()
      ->append(sprintf('@param %s $%s', $property->getType()->getDocType() ?: 'mixed', $property->getName()));

    return $gMethod;
  }

  protected function generateGetter(GProperty $property, $propertyDefinition) {
    $getterName = $this->inflector->getPropertyGetterName($property, $propertyDefinition);
    
    $gMethod = $this->gClass->createMethod(
      $getterName,
      array(),
      GFunctionBody::create(
        array(
          sprintf('return $this->%s;', $property->getName()),
        )
      ),
      GMethod::MODIFIER_PUBLIC
    );

    $gMethod->createDocBlock()
      ->append(sprintf('@return %s', $property->getType()->getDocType() ?: 'mixed'));

    return $gMethod;
  }

  protected function generateConstructor(GClass $gClass, stdClass $entity) {
    // @TODO: add a test to use the parent constructor if avaible (user author?)
    $code = array();
    $parameters = array();

    foreach ($entity->constructor as $propertyName => $parameter) {
      $propertyDefinition = $entity->properties->$propertyName;
      $property = $this->gClass->getProperty($propertyName);

      $code[] = sprintf('$this->%s = $%s;', $propertyName, $parameter->name);
      $parameters[] = new GParameter(
        $parameter->name,
        $property->getType(),
        $propertyDefinition->nullable ? NULL : GParameter::UNDEFINED
      );
    }

    $gClass->createMethod('__construct', $parameters, GFunctionBody::create($code));
  }

  protected function generateAssociationsAPI() {
    foreach ($this->gClass->getProperties() as $property) {
      if ($property->getType() instanceof PersistentCollectionType) {
        $propertyDefinition = $this->entity->properties->{$property->getName()};
        // eg author::posts, $subjectName = 'post' => $subjectEntity = 'Post'
        $subjectName = $this->inflector->getItemNameFromCollectionName($property->getName(), $propertyDefinition);
        $subjectEntity = $this->model->getEntity(ucfirst($subjectName));
        $subjectGClass = $this->gClassBroker->getElevated($subjectEntity->fqn, $this->entity->name);
        $subjectPropertyDefinition = $subjectEntity->properties->$subjectName;
        $subjectProperty = $subjectGClass->getPropery($subjectName);

        $generator = new AssociationsAPIGenerator(
          $this->inflector,
          $this->gClass,
          $this->entity,
          $subjectEntity
        );

        $generator->generateFor($property, $propertyDefinition);
      }
    }
  }


  protected function isEntityShortName($shortName) {
    return $this->model->hasEntity($shortName);
  }
}
