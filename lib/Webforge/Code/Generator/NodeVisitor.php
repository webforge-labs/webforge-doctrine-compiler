<?php

namespace Webforge\Code\Generator;

use InvalidArgumentException;
use PHPParser_Node;
use PHPParser_Node_Expr_Array;
use PHPParser_Node_Expr_ClassConstFetch;
use PHPParser_Node_Expr_ConstFetch;
use PHPParser_Node_Name_FullyQualified;
use PHPParser_Node_Param;
use PHPParser_Node_Scalar_DNumber;
use PHPParser_Node_Scalar_LNumber;
use PHPParser_Node_Scalar_String;
use PHPParser_Node_Stmt_Class;
use PHPParser_Node_Stmt_ClassConst;
use PHPParser_Node_Stmt_ClassMethod;
use PHPParser_Node_Stmt_Namespace;
use PHPParser_Node_Stmt_Property;
use PHPParser_Node_Stmt_Use;
use PHPParser_NodeVisitorAbstract;
use Webforge\Types\ArrayType;
use Webforge\Types\ObjectType;
use Webforge\Types\StringType;
use Webforge\Types\Type;

class NodeVisitor extends PHPParser_NodeVisitorAbstract
{
    protected $gClass;

    public function __construct(GClass $gClass = null)
    {
        $this->gClass = $gClass ?: new GClass();
    }

    public function leaveNode(PHPParser_Node $node)
    {
        if ($node instanceof PHPParser_Node_Stmt_Namespace) {
            //$this->gClass->setNamespace($node->name->toString());
        } elseif ($node instanceof PHPParser_Node_Stmt_Class) {
            $this->visitClass($node);
        } elseif ($node instanceof PHPParser_Node_Stmt_Use) {
            $this->visitUse($node);
        } elseif ($node instanceof PHPParser_Node_Stmt_ClassMethod) {
            $this->visitClassMethod($node);
        } elseif ($node instanceof PHPParser_Node_Stmt_Property) {
            $this->visitProperty($node);
        } elseif ($node instanceof PHPParser_Node_Stmt_ClassConst) {
            $this->visitConstant($node);
        } else {
            //throw $this->nodeTypeError($node, __FUNCTION__);
        }
        //var_dump(get_class($node));
    }

    protected function visitClass(PHPParser_Node_Stmt_Class $class)
    {
        $this->gClass->setFQN($class->namespacedName);

        if ($class->extends) {
            $this->gClass->setParent(new GClass($class->extends->toString()));
        }

        if ($class->getDocComment()) {
            $this->gClass->setDocBlock(new DocBlock($class->getDocComment()->getText()));
        }

        $this->gClass->setModifiers($this->createModifiers($class));
    }

    protected function visitUse(PHPParser_Node_Stmt_Use $useNode)
    {
        foreach ($useNode->uses as $use) {
            $this->gClass->addImport(new GClass($use->name->toString()), $use->alias);
        }
    }

    protected function visitClassMethod(PHPParser_Node_Stmt_ClassMethod $method)
    {
        $this->gClass->createMethod(
            $method->name,
            $this->createParameters($method->params),
            $this->createBody($method->stmts),
            $this->createModifiers($method),
            $method->byref
        );
    }

    protected function visitProperty(PHPParser_Node_Stmt_Property $node)
    {
        //$node is the  protected|public|static|private $prop1, $prop2, $prop3, ... expression
        $docBlock = $this->createDocBlock($node);
        foreach ($node->props as $property) {
            $this->gClass->createProperty(
                $property->name,
                $this->createType(null, $property->default, $docBlock),
                $property->default === null ? GProperty::UNDEFINED : $this->visitExpression($property->default),
                $this->createModifiers($node)
            );
        }
    }

    protected function visitConstant(PHPParser_Node_Stmt_ClassConst $node)
    {
        foreach ($node->consts as $constant) {
            $this->gClass->createConstant(
                $constant->name,
                $this->createType(null, $constant->value),
                $this->visitExpression($constant->value),
                GModifiersObject::MODIFIER_PUBLIC
            );
        }
    }

    protected function createParameters(array $parameterNodes)
    {
        $parameters = array();
        foreach ($parameterNodes as $node) {
            $parameters[] = $this->visitParameter($node);
        }
        return $parameters;
    }

    protected function visitParameter(PHPParser_Node_Param $param)
    {
        return GParameter::create(
            $param->name,
            $this->createType($param->type, $param->default),
            $param->default === null ? GParameter::UNDEFINED : $this->visitExpression($param->default),
            $param->byRef
        );
    }

    protected function visitExpression($node)
    {
        if ($node instanceof PHPParser_Node_Expr_Array) {
            return $this->visitArray($node);
        } elseif ($node instanceof PHPParser_Node_Scalar_String) {
            return $node->value;
        } elseif ($node instanceof PHPParser_Node_Scalar_LNumber) {
            return $node->value;
        } elseif ($node instanceof PHPParser_Node_Scalar_DNumber) {
            return $node->value;
        } elseif ($node instanceof PHPParser_Node_Expr_ConstFetch) {
            // @TODO wie setzen wir das hier?
            $constant = $node->name->toString();

            if ($constant === 'NULL') {
                return null;
            }

            return new GConstant($node->name);
        } elseif ($node instanceof PHPParser_Node_Expr_ClassConstFetch) {
            $constant = new GConstant($node->name);
            $constant->setGClass(new GClass($node->class->toString()));
            return $constant;
        }

        throw $this->nodeTypeError($node, __FUNCTION__);
    }

    protected function visitArray(PHPParser_Node_Expr_Array $node)
    {
        $items = array();
        $key = -1;
        foreach ($node->items as $arrayItem) {
            if ($arrayItem->key instanceof PHPParser_Node_Scalar_LNumber) {
                $key = $arrayItem->key->value;
            } elseif ($arrayItem->key instanceof PHPParser_Node_Scalar_String) {
                $key = $arrayItem->key->value;
            } else {
                // use self counting key
                $key++;
            }

            $items[$key] = $this->visitExpression($arrayItem->value);
        }
        return $items;
    }

    protected function createDocBlock($node)
    {
        if (($comment = $node->getDocComment()) != null) {
            return new DocBlock($comment->getText());
        }
    }

    protected function createBody($stmts)
    {
        return new GFunctionBody();
    }

    protected function createType($type, $nodeValue, DocBlock $docBlock = null)
    {
        if ($type === null) {
            // we could use an agent approach here to find direct conflicting reads like:
            // nodeValue is instanceof String but @var in DocComment is array, e.g.

            if ($nodeValue instanceof PHPParser_Node_Scalar_String) {
                return new StringType();
            } elseif ($nodeValue instanceof PHPParser_Node_Expr_Array) {
                return new ArrayType();
            } elseif (isset($docBlock) && $docBlock->hasSimpleAnnotation('var')) {
                return Type::parseFromDocBlock($docBlock->parseSimpleAnnotation('var'));
            }

            return null;
        } elseif ($type instanceof PHPParser_Node_Name_FullyQualified) {
            return new ObjectType(new GClass($type->toString()));
        } elseif ($type == 'array') {
            return new ArrayType();
        }

        throw $this->nodeTypeError($type, __FUNCTION__);
    }

    protected function createModifiers($object)
    {
        $modifiers = 0x000000;
        $type = $object->type;

        if ($type & PHPParser_Node_Stmt_Class::MODIFIER_PUBLIC) {
            $modifiers |= GModifiersObject::MODIFIER_PUBLIC;
        }

        if ($type & PHPParser_Node_Stmt_Class::MODIFIER_PROTECTED) {
            $modifiers |= GModifiersObject::MODIFIER_PROTECTED;
        }

        if ($type & PHPParser_Node_Stmt_Class::MODIFIER_PRIVATE) {
            $modifiers |= GModifiersObject::MODIFIER_PRIVATE;
        }

        if ($type & PHPParser_Node_Stmt_Class::MODIFIER_FINAL) {
            $modifiers |= GModifiersObject::MODIFIER_FINAL;
        }

        if ($type & PHPParser_Node_Stmt_Class::MODIFIER_ABSTRACT) {
            $modifiers |= GModifiersObject::MODIFIER_ABSTRACT;
        }

        if ($type & PHPParser_Node_Stmt_Class::MODIFIER_STATIC) {
            $modifiers |= GModifiersObject::MODIFIER_STATIC;
        }

        return $modifiers;
    }


    protected function nodeTypeError($node, $function)
    {
        return new InvalidArgumentException('Unknown NodeType: ' . get_class($node) . ' in Branch: ' . $function);
    }

    public function getGClass()
    {
        return $this->gClass;
    }
}
