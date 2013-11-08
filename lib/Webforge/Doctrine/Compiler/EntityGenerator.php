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

class EntityGenerator {

  protected $entity;
  protected $gClass;
  protected $inflector;
  protected $mappingGenerator;
  protected $model;

  public function __construct(Inflector $inflector, EntityMappingGenerator $mappingGenerator) {
    $this->inflector = $inflector;
    $this->mappingGenerator = $mappingGenerator;
  }

  public function generate(stdClass $entity, $fqn, Model $model) {
    $this->model = $model;
    $this->entity = $entity;
    $this->gClass = new GClass($fqn);

    $this->generateProperties($this->entity->properties);

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
    // $gMethod->createDocBlock()->addSimpleAnnotation('param '.($property->getDocType() ?: 'undefined').' $'.$property->getName());

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

    return $gMethod;
  }

  protected function parseType($typeDefinition) {
    $typeName = $typeDefinition;
    if ($typeDefinition === 'DefaultId') {
      $typeName = 'Id';
    }

    if ($this->isEntityShortName($typeName)) {
      $referenceEntityName = $typeName;
      $type = new PersistentCollectionType(new GClass($this->model->getEntity($referenceEntityName)->fqn));
      return $type;
    }

    if (S::startsWith('Collection', $typeName)) {
      $referenceEntityName = $typeName;
    }

    try {
      return Type::create($typeName);
    } catch (TypeException $e) {
      throw new InvalidModelException(sprintf("The type: '%s' cannot be parsed for entity '%s'.", $typeName, $this->entity->fqn), 0, $e);
    }
  }

  protected function isEntityShortName($shortName) {
    return $this->model->hasEntity($shortName);
  }
}
