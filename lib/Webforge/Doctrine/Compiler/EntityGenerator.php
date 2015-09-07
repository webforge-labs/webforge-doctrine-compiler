<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Code\Generator\GClass;
use Webforge\Code\Generator\GProperty;
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
use Webforge\Common\ClassInterface;

class EntityGenerator {

  /**
   * @var GeneratedEntity[]
   */
  protected $generated;

  protected $inflector;
  protected $mappingGenerator;
  protected $model;
  protected $broker;

  public function __construct(Inflector $inflector, EntityMappingGenerator $mappingGenerator, GClassBroker $broker) {
    $this->inflector = $inflector;
    $this->mappingGenerator = $mappingGenerator;
    $this->broker = $broker;
  }

  public function generate(Model $model) {
    $this->model = $model;

    $this->generated = array();

    foreach ($model->getEntities() as $entityDefinition) {
      $this->generated[$entityDefinition->fqn] = $entity = new GeneratedEntity($entityDefinition, new GClass($entityDefinition->fqn));
      $entity->inflect($this->inflector);
    }

    foreach ($this->generated as $entity) {
      $this->completeEntity($entity);
    }

    foreach ($this->generated as $entity) {
      $this->model->indexAssociations($entity);
    }
    $this->model->completeAssociations();

    foreach ($this->generated as $entity) {
      $this->generateConstructor($entity);
      $this->generateAssociationsAPI($entity);

      $this->mappingGenerator->init($entity, $this->model);
      $this->mappingGenerator->annotate($entity->gClass);
    }
  }

  protected function completeEntity(GeneratedEntity $entity) {
    if ($entity->definition->extends) {

      if ($entity->definition->extends instanceof ClassInterface) {
        $entity->setParent($this->broker->getElevated($entity->definition->extends, $entity->getName()));
      } else{
        $fqn = $entity->definition->extends->fqn;
        $entity->setParent($this->getEntity($fqn));
      }
    }

    $this->generateProperties($entity);
  }

  protected function generateProperties(GeneratedEntity $entity) {
    foreach ($entity->definition->properties as $name => $propertyDefinition) {
      $this->generatePropertyType($propertyDefinition);

      $gProperty = $entity->gClass->createProperty(
        $name,
        $propertyDefinition->type,
        $default = GClass::UNDEFINED,
        $modifiers = GProperty::MODIFIER_PROTECTED
      );
      $gProperty->interpretDefaultValueLiterally();

      $property = new GeneratedProperty($propertyDefinition, $gProperty);
      $property->inflect($this->inflector);

      if ($property->hasDefaultValue()) {
        $gProperty->setDefaultValue($property->getDefaultValue());
      }

      $entity->addProperty($property);

      $this->generateGetter($property, $entity);
      $this->generateSetter($property, $entity);
    }
  }

  protected function generatePropertyType(stdClass $def) {
    if ($def->type instanceof EntityReference) {
      $def->reference = $def->type;
      $def->referencedEntity = $this->getEntity($def->reference->getFQN());
    
      if ($def->reference instanceof EntityCollectionReference) {
        $def->type = new PersistentCollectionType($def->referencedEntity->getGClass());
      } else {
        $def->type = new EntityType($def->referencedEntity->getGClass());
      }
    }
  }

  protected function generateSetter(GeneratedProperty $property, GeneratedEntity $entity) {
    $gMethod = $entity->gClass->createMethod(
      $property->getSetterName(),
      array(
        $parameter = $property->getParameter()
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
      ->append(sprintf('@param %s $%s', $property->getDocType(), $property->getName()));

    return $gMethod;
  }

  protected function generateGetter(GeneratedProperty $property, GeneratedEntity $entity) {
    $gMethod = $entity->gClass->createMethod(
      $property->getGetterName(),
      array(),
      GFunctionBody::create(
        array(
          sprintf('return $this->%s;', $property->getName()),
        )
      ),
      GMethod::MODIFIER_PUBLIC
    );

    $gMethod->createDocBlock()
      ->append(sprintf('@return %s', $property->getDocType()));

    return $gMethod;
  }

  protected function generateConstructor(GeneratedEntity $entity) {
    $code = array();

    $parameters = array();

    // parent can either be a "normal" class or an entity
    if (($parent = $entity->getParentClass()) && $parent->hasMethod('__construct')) {
      $parentParameters = array();
      foreach ($parent->getMethod('__construct')->getParameters() as $parameter) {
        $parentParameters[] = '$'.$parameter->getName();
        $parameters[$parameter->getName()] = $parameter;
      }

      $code[] = 'parent::__construct('.implode(',', $parentParameters).');';
    }

    $constructed = array();
    foreach ($entity->definition->constructor as $propertyName => $parameterDefinition) {
      $property = $entity->getProperty($propertyName);
      $gParameter = clone $property->getParameter();
      $constructed[$property->getName()] = $property;

      if (isset($parameterDefinition->defaultValue)) {
        $gParameter->interpretDefaultValueLiterally();
        $gParameter->setDefault($parameterDefinition->defaultValue);
      }

      if ($property->isEntity()) {
        $code[] = sprintf('if (isset($%s)) {', $gParameter->getName());
        $code[] = sprintf('  $this->%s($%s);', $property->getSetterName(), $gParameter->getName());
        $code[] = '}';
      } else {
        $code[] = sprintf('$this->%s = $%s;', $property->getName(), $gParameter->getName());
      }

      if (!array_key_exists($gParameter->getName(), $parameters)) {
        $parameters[] = $gParameter;
      }
    }

    foreach ($entity->getProperties() as $property) {
      if ($property->isEntityCollection() && !array_key_exists($property->getName(), $constructed)) {
        $entity->gClass->addImport($property->getType()->getGClass(), 'ArrayCollection');

        $code[] = sprintf('$this->%s = new ArrayCollection();', $property->getName());
      }
    }

    $constructor = $entity->gClass->createMethod('__construct', $parameters, GFunctionBody::create($code));

    $entity->gClass->setMethodOrder($constructor, $position = 0);
  }

  protected function generateAssociationsAPI(GeneratedEntity $entity) {
    foreach ($entity->getProperties() as $property) {
      if ($property->hasReference()) {
        $generator = new AssociationsAPIGenerator(
          $this->inflector,
          $entity
        );

        $generator->generateFor(
          $this->model->getAssociationFor($entity, $property),
          $property
        );
      }
    }
  }

  public function getEntities() {
    return $this->generated;
  }

  protected function getEntity($fqn) {
    return $this->generated[$fqn];
  }

  protected function isEntity($fqn) {
    return array_key_exists($fqn, $this->generated);
  }
}
