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

  /**
   * @var GeneratedEntity[]
   */
  protected $generated;

  protected $inflector;
  protected $mappingGenerator;
  protected $model;
  protected $gClassBroker;

  public function __construct(Inflector $inflector, EntityMappingGenerator $mappingGenerator) {
    $this->inflector = $inflector;
    $this->mappingGenerator = $mappingGenerator;
  }

  public function generate(Model $model, GClassBroker $broker) {
    $this->model = $model;

    $this->generated = array();

    foreach ($model->getEntities() as $entityDefinition) {
      $this->generated[$entityDefinition->fqn] = new GeneratedEntity($entityDefinition, new GClass($entityDefinition->fqn));
    }

    foreach ($this->generated as $entity) {
      $this->completeEntity($entity, $broker);
    }

    foreach ($this->generated as $entity) {
      $this->model->indexAssociations($entity);
    }
    $this->model->completeAssociations();

    foreach ($this->generated as $entity) {
      $this->generateAssociationsAPI($entity);
      $this->generateConstructor($entity);

      $this->mappingGenerator->init($entity);
      $this->mappingGenerator->annotate($entity->gClass);
    }
  }

  protected function completeEntity(GeneratedEntity $entity, GClassBroker $broker) {
    if ($entity->definition->extends) {
      $fqn = $entity->definition->extends->fqn;

      if ($this->isEntity($fqn)) {
        $entity->setParent($this->getEntity($fqn));
      } else {
        $entity->setParent($broker->getElevated($fqn));
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

      $property = new GeneratedProperty($propertyDefinition, $gProperty);
      $property->inflect($this->inflector);
      $entity->addProperty($property);

      $this->generateSetter($property, $entity);
      $this->generateGetter($property, $entity);
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
    // @TODO: add a test to use the parent constructor if avaible (user author?)
    $code = array();
    $parameters = array();

    $constructed = array();
    foreach ($entity->definition->constructor as $propertyName => $parameter) {
      $property = $entity->getProperty($propertyName);
      $parameter = $property->getParameter();
      $constructed[$property->getName()] = $property;

      if ($property->isEntity()) {
        $code[] = sprintf('if (isset($%s)) {', $parameter->getName());
        $code[] = sprintf('  $this->%s($%s);', $property->getSetterName(), $parameter->getName());
        $code[] = '}';
      } else {
        $code[] = sprintf('$this->%s = $%s;', $property->getName(), $parameter->getName());
      }

      $parameters[] = $parameter;
    }

    foreach ($entity->getProperties() as $property) {
      if ($property->isEntityCollection() && !array_key_exists($property->getName(), $constructed)) {
        $entity->gClass->addImport($property->getType()->getGClass(), 'ArrayCollection');

        $code[] = sprintf('$this->%s = new ArrayCollection();', $property->getName());
      }
    }

    $entity->gClass->createMethod('__construct', $parameters, GFunctionBody::create($code));
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

  protected function isEntityShortName($shortName) {
    return $this->model->hasEntity($shortName);
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
