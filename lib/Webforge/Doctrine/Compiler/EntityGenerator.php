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

class EntityGenerator {

  protected $entity;
  protected $gClass;
  protected $inflector;

  public function __construct(Inflector $inflector) {
    $this->inflector = $inflector;
  }

  public function generate(stdClass $entity, $fqn) {
    $this->entity = $entity;
    $this->gClass = new GClass($fqn);

    $this->generateProperties($this->entity->members);

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

    return Type::create($typeName);
  }
}
