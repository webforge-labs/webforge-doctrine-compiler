<?php

namespace Webforge\Doctrine\Compiler;

use stdClass;
use Webforge\Code\Generator\GFunctionBody;
use Webforge\Code\Generator\GMethod;
use Webforge\Code\Generator\GParameter;
use Webforge\Types\EntityType;

class AssociationsAPIGenerator
{
    protected $entity;
    protected $inflector;

    public function __construct(Inflector $inflector, GeneratedEntity $entity)
    {
        $this->inflector = $inflector;
        $this->entity = $entity;
    }

    public function generateFor(stdClass $associationsPair, GeneratedProperty $property)
    {
        if ($this->entity->equals($associationsPair->owning->entity)) {
            $association = $associationsPair->owning;
        } else {
            $association = $associationsPair->inverse;
        }

        if ($property->isEntity()) {
            $this->injectIntoSetter($property, $association);
        }

        if ($property->isEntityCollection()) {
            $add = $this->generateDoer('add', $property, $association);
            $remove = $this->generateDoer('remove', $property, $association);
            $has = $this->generateDoer('has', $property, $association);
            $this->orderMethods(array($add, $remove, $has), $property);
        }
    }

    protected function generateDoer($type, GeneratedProperty $property, ModelAssociation $association)
    {
        $collectionName = $property->getName();

        // writtenPosts => writtenPost
        $paramName = $this->inflector->getItemNameFromCollectionName($collectionName, $property->getDefinition());

        $parameter = new GParameter(
            $paramName,
            new EntityType($association->referencedEntity->getGClass()),
            $property->getDefinition()->nullable ? null : GParameter::UNDEFINED
        );

        $updateOtherside = $association->shouldUpdateOtherSide();

        $body = array();
        $docBlock = array();
        switch ($type) {
            case 'add':
                $body[] = sprintf('if (!$this->%s->contains($%s)) {', $collectionName, $parameter->getName());
                $body[] = sprintf('  $this->%s->add($%s);', $collectionName, $parameter->getName());
                if ($updateOtherside) {
                    $body[] = sprintf(
                        '  $%s->%s($this);',
                        $parameter->getName(),
                        $association->referencedProperty->getCollectionDoerName('add')
                    );
                }
                $body[] = '}';
                $body[] = 'return $this;';

                $docBlock[] = sprintf(
                    '@param %s $%s',
                    $association->referencedEntity->getDocType(),
                    $parameter->getName()
                );
                break;

            case 'remove':
                $body[] = sprintf('if ($this->%s->contains($%s)) {', $collectionName, $parameter->getName());
                $body[] = sprintf('  $this->%s->removeElement($%s);', $collectionName, $parameter->getName());
                if ($updateOtherside) {
                    $body[] = sprintf(
                        '  $%s->%s($this);',
                        $parameter->getName(),
                        $association->referencedProperty->getCollectionDoerName('remove')
                    );
                }
                $body[] = '}';
                $body[] = 'return $this;';

                $docBlock[] = sprintf(
                    '@param %s $%s',
                    $association->referencedEntity->getDocType(),
                    $parameter->getName()
                );
                break;

            case 'has':
                $body = array(
                    sprintf('return $this->%s->contains($%s);', $collectionName, $parameter->getName()),
                );

                $docBlock[] = sprintf(
                    '@param %s $%s',
                    $association->referencedEntity->getDocType(),
                    $parameter->getName()
                );
                $docBlock[] = '@return bool';
                break;
        }

        $method = $this->entity->gClass->createMethod(
            $property->getCollectionDoerName($type),
            array(
                $parameter
            ),
            GFunctionBody::create($body),
            GMethod::MODIFIER_PUBLIC
        );

        if (count($docBlock) > 0) {
            $block = $method->createDocBlock();
            foreach ($docBlock as $line) {
                $block->append($line);
            }
        }

        return $method;
    }

    protected function injectIntoSetter(GeneratedProperty $property, ModelAssociation $association)
    {
        if ($association->shouldUpdateOtherSide()) {
            $setter = $this->entity->gClass->getMethod($property->getSetterName());
            $oneSideParam = $setter->getParameterByIndex(0);

            // remove from previous one side (inject this before setting)
            $remove = array();
            $remove[] = sprintf(
                'if (isset($this->%1$s) && $this->%1$s !== $%2$s) $this->%1$s->%3$s($this);',
                $property->getName(),
                $oneSideParam->getName(),
                $association->referencedProperty->getCollectionDoerName('remove')
            );
            $setter->getBody()->insertBody($remove, 0);

            // add to new one side (inject this after setting)
            $add = array();
            if ($oneSideParam->isOptional()) {
                // setting the many side to NULL is allowed, so we have to check if the param is NULL and were able to add (notice that remove is already done before)
                $add[] = sprintf(
                    'if (isset($%1$s)) $%1$s->%2$s($this);',
                    $oneSideParam->getName(),
                    $association->referencedProperty->getCollectionDoerName('add')
                );
                $setter->getBody()->insertBody($add, 2);
            } else {
                $add[] = sprintf(
                    '$%1$s->%2$s($this);',
                    $oneSideParam->getName(),
                    $association->referencedProperty->getCollectionDoerName('add')
                );
                $setter->getBody()->insertBody($add, 2);
            }
        }
    }

    protected function orderMethods(array $methods, GeneratedProperty $property)
    {
        $position = null;
        foreach ($this->entity->gClass->getMethods() as $mposition => $method) {
            if ($method->getName() === $property->getSetterName()) {
                $position = $mposition;
                break;
            }
        }

        if ($position !== null) {
            foreach ($methods as $order => $method) {
                $this->entity->gClass->setMethodOrder($method, $position + 1 + $order);
            }
        }
    }
}
