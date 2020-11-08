<?php

namespace Webforge\Doctrine\Compiler;

use ReflectionException;
use stdClass;
use Webforge\Code\Generator\GClass;
use Webforge\Common\ClassUtil;
use Webforge\Common\StringUtil as S;
use Webforge\Doctrine\Compiler\Types\DecimalType;
use Webforge\Doctrine\Compiler\Types\JsonType;
use Webforge\Types\CollectionType;
use Webforge\Types\DCEnumType;
use Webforge\Types\ObjectType;
use Webforge\Types\Type;

class ModelValidator
{
    protected $model;

    public function validateModel(stdClass $model)
    {
        if (!isset($model->namespace) || empty($model->namespace)) {
            throw new InvalidModelException('The .namespace cannot be empty');
        }

        if (!isset($model->entities) || !is_array($model->entities)) {
            throw new InvalidModelException('The .entities have to be an array');
        }

        $entities = [];
        foreach ($model->entities as $key => $entity) {
            $entities[$key] = $this->validateEntity($entity, $key, $model);
        }

        $this->model = new Model(
            $model->namespace,
            $entities,
            isset($model->collectionType) ? $model->collectionType : 'default'
        );

        // snd pass: check names, check entity property references
        foreach ($this->model->getEntities() as $entity) {
            if (isset($entity->extends)) {
                if (empty($entity->extends)) {
                    throw new InvalidModelException('Entity in model with key "' . $key . '" has to have a non empty value in "extends"');
                }

                if ($this->model->hasEntity($entity->extends)) {
                    $entity->extends = $this->model->getEntity($entity->extends);
                } elseif (class_exists($entity->extends)) {
                    $entity->extends = new GClass($entity->extends);
                } else {
                    throw new InvalidModelException('Entity ' . $entity->name . ' extends an unknown entity "' . $entity->extends . '".');
                }
            } else {
                $entity->extends = null;
            }

            // check entity property references
            foreach ($entity->properties as $name => $property) {
                $this->validateType($property, $entity, $this->model);
            }
        }

        return $this->model;
    }

    protected function validateEntity($entity, $key, stdClass $model)
    {
        if (!($entity instanceof stdClass)) {
            throw new InvalidModelException('Entity in model with key "' . $key . '" has to be an object');
        }

        if (!isset($entity->name) || empty($entity->name)) {
            throw new InvalidModelException('Entity in model with key "' . $key . '" has to have a non empty property name');
        }

        $entity->fqn = ClassUtil::expandNamespace($entity->name, $model->namespace);

        if (!S::startsWith($entity->fqn, $model->namespace)) {
            $entity->fqn = ClassUtil::setNamespace($entity->fqn, $model->namespace);
        }

        if (!isset($entity->properties)) {
            $entity->properties = new stdClass();
        }

        foreach ($entity->properties as $name => $propertyDefinition) {
            $entity->properties->$name = $this->validateProperty($propertyDefinition, $name, $entity->name);
        }

        if (!isset($entity->constructor)) {
            $entity->constructor = [];
        }

        $constructor = new stdClass();
        foreach ($entity->constructor as $key => $value) {
            if (is_string($value)) {
                $definition = (object)['name' => $value];
            } elseif (is_object($value)) {
                if (!isset($value->name)) {
                    throw new InvalidModelException(
                        sprintf(
                            "Invalid object as constructor argument: %s in the constructor from entity %s. Only property-names or objects with property .name and .defaultValue can be used",
                            json_encode($value),
                            $entity->name
                        )
                    );
                }

                $definition = $value;
            } else {
                throw new InvalidModelException(
                    sprintf(
                        "Invalid value-type as constructor argument: %s in the constructor from entity %s. Only property-names or objects with property .name and .defaultValue can be used",
                        gettype($value),
                        $entity->name
                    )
                );
            }

            if (!isset($entity->properties->{$definition->name})) {
                throw new InvalidModelException(
                    sprintf(
                        "Undefined property '%s' in the constructor from entity %s. Use an existing property-name",
                        $definition->name,
                        $entity->name
                    )
                );
            }

            $constructor->{$definition->name} = $definition;
        }
        $entity->constructor = $constructor;

        return $entity;
    }

    protected function validateProperty($definition, $name, $entityName)
    {
        if (is_string($definition)) {
            $definition = (object)[
                'type' => $definition
            ];
        }

        if (!($definition instanceof stdClass)) {
            throw new InvalidModelException('Definition of the property with name "' . $name . '" in entity "' . $entityName . '" has to be an object');
        }

        if (!isset($definition->type)) {
            $definition->type = 'String';
        }

        if (!isset($definition->nullable)) {
            $definition->nullable = false;
        }

        $definition->name = $name;

        return $definition;
    }

    protected function validateType(stdClass $property, stdClass $entity, Model $model)
    {
        $typeName = $property->type;
        if ($property->type === 'DefaultId') {
            $typeName = 'Id';
        }

        if ($model->hasEntity($typeName)) {
            $type = new EntityReference($this->model->getEntity($typeName));
        } else {
            try {
                $type = $this->createType($typeName);
            } catch (ReflectionException $e) {
                throw new InvalidModelException(sprintf(
                    "The type: '%s' cannot be parsed for entity '%s'.",
                    $typeName,
                    $entity->fqn
                ), 0, $e);
            }

            if ($type instanceof CollectionType && $type->getType() instanceof ObjectType) {
                if ($model->hasEntity($referenceEntityName = $type->getType()->getClass()->getFQN())) { // FQN is here subnamespace + name
                    $type = new EntityCollectionReference($model->getEntity($referenceEntityName));
                } else {
                    throw new InvalidModelException(
                        sprintf(
                            "The entityName '%s' in type from property %s::%s cannot be found in model. Did you misspelled the name of the entity?",
                            $referenceEntityName,
                            $entity->fqn,
                            $property->name
                        )
                    );
                }
            }

            if ($type instanceof DCEnumType) {
                // register types that arent registered, because doctrine will complain about not registered types
                $typeName = str_replace('\\', '', $typeClass = $type->getClass()->getFQN());

                if (!\Doctrine\DBAL\Types\Type::hasType($typeName)) {
                    \Doctrine\DBAL\Types\Type::addType($typeName, $typeClass);
                }
            }
        }

        $property->type = $type;
    }

    private function createType($typeName)
    {
        if ($typeName === 'Json') {
            return new JsonType();
        } elseif ($typeName === 'Decimal') {
            return new DecimalType();
        } else {
            return Type::create($typeName);
        }
    }
}
