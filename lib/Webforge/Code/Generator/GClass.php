<?php

namespace Webforge\Code\Generator;

use ReflectionClass;
use Webforge\Common\ClassInterface;
use Webforge\Common\ClassUtil;
use Webforge\Common\StringUtil as S;
use Webforge\Common\System\File;
use Webforge\Types\ObjectType;

class GClass extends GModifiersObject implements ClassInterface
{
    public const WITHOUT_CONSTRUCTOR = true;
    public const END = GObjectCollection::END;

    public const WITH_OWN = 0x000001;
    public const WITH_INTERFACE = 0x000002;
    public const WITH_PARENTS = 0x000004;
    public const WITH_PARENTS_INTERFACES = 0x000008;

    public const WITH_EXTENDS = 0x000010;

    public const FULL_HIERARCHY = 0x00000F;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var Webforge\Code\Generator\GClass
     */
    protected $parentClass;

    /**
     * @var GObjectCollection(Webforge\Code\Generator\GClass)
     */
    protected $interfaces;

    /**
     * @var GObjectCollection(Webforge\Code\Generator\GProperty)
     */
    protected $properties;

    /**
     * @var GObjectCollection(Webforge\Code\Generator\GMethod)
     */
    protected $methods;

    /**
     * @var GObjectCollection(Webforge\Code\Generator\GConstant)
     */
    protected $constants;

    /**
     * The personal imports of the GClass
     *
     * @var Webforge\Code\Generator\Imports
     */
    protected $ownImports;


    public function __construct($class = null)
    {
        $this->ownImports = new Imports();
        $this->interfaces = new GObjectCollection(array());
        $this->methods = new GObjectCollection(array());
        $this->properties = new GObjectCollection(array());
        $this->constants = new GObjectCollection(array());

        if ($class instanceof GClass) {
            $this->setFQN($class->getFQN());
        } elseif (is_string($class)) {
            $this->setFQN($class);
        }
    }

    /**
     * @return \Webforge\Code\Generator\GClass
     */
    public static function create($fqn, $parentClass = null): self
    {
        $gClass = new static($fqn);

        if (isset($parentClass)) {
            if (is_string($parentClass)) {
                $parentClass = self::create($parentClass);
            }

            $gClass->setParent($parentClass);
        }

        return $gClass;
    }

    /**
     * @return Object<{$class}>
     */
    public static function newClassInstance($class, array $params = array())
    {
        if ($class instanceof GClass) {
            return $class->newInstance($params);
        } else {
            return ClassUtil::newClassInstance($class, $params);
        }
    }

    /**
     * @return Object<{$this->getFQN()}>
     */
    public function newInstance(array $params = array(), $dontCallConstructor = false)
    {
        if ($dontCallConstructor) {
            // Creates a new instance of the mapped class, without invoking the constructor.
            if (!isset($this->prototype)) {
                $this->prototype = unserialize(sprintf('O:%d:"%s":0:{}', mb_strlen($this->getFQN()), $this->getFQN()));
            }

            return clone $this->prototype;
        }

        return $this->getReflection()->newInstanceArgs($params);
    }

    /**
     * @return ReflectionClass
     */
    public function getReflection()
    {
        return new ReflectionClass($this->getFQN());
    }

    /**
     * Returns the file where the GClass is defined
     *
     * but this works only if the GClass is existing and written
     */
    public function getFile()
    {
        return new File($this->getReflection()->getFileName());
    }

    /**
     * Creates a new property and adds it to the class
     *
     * notice: this is not chainable, you can leave the chain with getGClass()
     * @return GProperty
     */
    public function createProperty(
        $name,
        $type = null,
        $default = self::UNDEFINED,
        $modifiers = GProperty::MODIFIER_PROTECTED
    ) {
        $gProperty = GProperty::create($name, $type, $default, $modifiers);

        $this->addProperty($gProperty);
        return $gProperty;
    }

    /**
     * Creates a new constant and adds it to the class
     *
     * notice: this is not chainable, you can leave the chain with getGClass()
     * @return Gconstant
     */
    public function createConstant(
        $name,
        $type = null,
        $default = self::UNDEFINED,
        $modifiers = GConstant::MODIFIER_PROTECTED
    ) {
        $gConstant = GConstant::create($name, $type, $default, $modifiers);

        $this->addConstant($gConstant);
        return $gConstant;
    }

    /**
     * Creates a new Method and adds it to the class
     *
     * notice: this returns a gMethod and is not chainable
     * but you can "leave" the chain with getGClass()
     * @return GMethod
     */
    public function createMethod($name, $params = array(), $body = null, $modifiers = GMethod::MODIFIER_PUBLIC)
    {
        $method = new GMethod($name, $params, $body, $modifiers);
        $method->setGClass($this);

        $this->addMethod($method);

        return $method;
    }

    /**
     * Erstellt Stubs (Prototypen) für alle abstrakten Methoden der Klasse
     */
    public function createAbstractMethodStubs()
    {
        if ($this->isAbstract() || $this->isInterface()) {
            return $this;
        }

        foreach ($this->getAllMethods(self::FULL_HIERARCHY & ~self::WITH_OWN) as $method) {
            //if ($this->needsImplementation($method)) {
            $this->createMethodStub($method);
            //} // performance: this will be already checked in createMethodStub
        }

        return $this;
    }

    /**
     * Returns if the method needs implementation in this class
     * @return bool
     */
    protected function needsImplementation(Gmethod $method)
    {
        return ($method->isAbstract() || $method->isInInterface()) && !$this->hasMethod($method->getName());
    }

    /**
     * Erstellt einen Stub für eine gegebene abstrakte Methode
     */
    public function createMethodStub(GMethod $method)
    {
        if (!$this->needsImplementation($method)) {
            return $this;
        }

        $cMethod = clone $method;
        $cMethod->setAbstract(false);

        return $this->addMethod($cMethod);
    }

    /**
     * returns the Name of the Class
     *
     * its the Name of the FQN without the Namespace
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the Name of the Class
     *
     * this is not the FQN, its only the FQN without the namespace
     * @chainable
     */
    public function setName($name)
    {
        $this->name = trim($name, '\\');
        return $this;
    }

    /**
     * @chainable
     */
    public function setNamespace($ns)
    {
        $this->namespace = ltrim(S::expand($ns, '\\', S::END), '\\');
        return $this;
    }

    /**
     * Returns the Namespace
     *
     * @return string The namespace has no \ before and after
     */
    public function getNamespace()
    {
        // i think its faster to compute the FQN with concatenation add the trailingslash in the setter and remove the trailslash here
        return isset($this->namespace) ? rtrim($this->namespace, '\\') : null;
    }

    /**
     * Tests if the namespace of the class is a subnamespace of the given
     *
     */
    public function isInNamespace($namespace)
    {
        if ($namespace === null) {
            return true;
        }
        if ($this->getNamespace() == null) {
            return false;
        }

        return mb_strpos($this->namespace, $namespace) === 0;
    }

    /**
     * Returns the Fully Qualified Name of the Class
     *
     * this is the whole path including Namespace without a backslash before
     * @return string
     */
    public function getFQN()
    {
        return $this->namespace . $this->name;
    }

    public function getKey()
    {
        return $this->getFQN();
    }

    /**
     * Replaces the Namespace and Name of the Class
     *
     * @param string $fqn no \ before
     */
    public function setFQN($fqn)
    {
        if (false !== ($pos = mb_strrpos($fqn, '\\'))) {
            $this->namespace = ltrim(
                mb_substr($fqn, 0, $pos + 1),
                '\\'
            ); // +1 to add the trailing slash, see setNamespace
            $this->setName(mb_substr($fqn, $pos));
        } else {
            $this->namespace = null;
            $this->setName($fqn);
        }
    }

    /**
     * @return bool
     */
    public function isInterface()
    {
        return $this instanceof GInterface;
    }

    /**
     * @chainable
     */
    public function addInterface(GClass $interface, $position = self::END)
    {
        $this->interfaces->add($interface, $position);
        return $this;
    }

    /**
     * @return GClass
     */
    public function getInterface($fqnOrIndex)
    {
        return $this->interfaces->get($fqnOrIndex);
    }

    /**
     * @return bool
     */
    public function hasInterface($fqnOrClass)
    {
        return $this->interfaces->has($fqnOrClass);
    }

    /**
     * @chainable
     */
    public function removeInterface($fqnOrClass)
    {
        $this->interfaces->remove($fqnOrClass);
        return $this;
    }

    /**
     * @chainable
     */
    public function setInterfaceOrder($interface, $position)
    {
        $this->interfaces->setOrder($interface, $position);
        return $this;
    }

    /**
     * @return array
     */
    public function getInterfaces()
    {
        return $this->interfaces->toArray();
    }

    /**
     * Returns the interfaces from the class and from all parents
     * @return array
     */
    public function getAllInterfaces($types = self::FULL_HIERARCHY)
    {
        if ($types & self::WITH_OWN) {
            $interfaces = clone $this->interfaces;
        } else {
            $interfaces = new GObjectCollection(array());
        }

        if (($types & self::WITH_PARENTS) && ($parent = $this->getParent()) != null) {
            $parentTypes = $types | self::WITH_OWN;
            foreach ($parent->getAllInterfaces($parentTypes) as $interface) {
                if (!$interfaces->has($interface)) {
                    $interfaces->add($interface);
                }
            }
        }

        return $interfaces->toArray();
    }

    /**
     * @param array
     */
    public function setInterfaces(array $interfaces)
    {
        $this->interfaces = new GObjectCollection($interfaces);
        return $this;
    }

    /**
     * @chainable
     */
    public function addConstant(GConstant $constant, $position = self::END)
    {
        $this->constants->add($constant, $position);
        $constant->setGClass($this);
        return $this;
    }

    /**
     * @return GClass
     */
    public function getConstant($nameOrIndex)
    {
        return $this->constants->get($nameOrIndex);
    }

    /**
     * @return bool
     */
    public function hasConstant($fqnOrClass)
    {
        return $this->constants->has($fqnOrClass);
    }

    /**
     * @chainable
     */
    public function removeConstant($fqnOrClass)
    {
        $this->constants->remove($fqnOrClass);
        return $this;
    }

    /**
     * @chainable
     */
    public function setConstantOrder(GConstant $constant, $position)
    {
        $this->constants->setOrder($constant, $position);
        return $this;
    }

    /**
     * @return array
     */
    public function getConstants()
    {
        return $this->constants->toArray();
    }

    /**
     * @param array
     */
    public function setConstants(array $constants)
    {
        $this->constants = new GObjectCollection(array());
        foreach ($constants as $constant) {
            $this->addConstant($constant);
        }
        return $this;
    }

    /**
     * @chainable
     */
    public function addProperty(GProperty $property, $position = self::END)
    {
        $this->properties->add($property, $position);
        $property->setGClass($this);
        return $this;
    }

    /**
     * @return GClass
     */
    public function getProperty($nameOrIndex)
    {
        return $this->properties->get($nameOrIndex);
    }

    /**
     * @return bool
     */
    public function hasProperty($nameOrClass)
    {
        return $this->properties->has($nameOrClass);
    }

    /**
     * @chainable
     */
    public function removeProperty($nameOrClass)
    {
        $this->properties->remove($nameOrClass);
        return $this;
    }

    /**
     * @chainable
     */
    public function setPropertyOrder($property, $position)
    {
        $this->properties->setOrder($property, $position);
        return $this;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties->toArray();
    }

    /**
     * Returns the properties of the class and the properties of all parents
     *
     * @return array
     */
    public function getAllProperties($types = self::FULL_HIERARCHY)
    {
        if ($types & self::WITH_OWN) {
            $properties = clone $this->properties;
        } else {
            $properties = new GObjectCollection(array());
        }

        if ($types & self::WITH_PARENTS && $this->getParent() != null) {
            // treat duplicates (aka: overriden properties):
            $parentTypes = $types | self::WITH_OWN;
            foreach ($this->getParent()->getAllProperties($parentTypes) as $property) {
                if (!$properties->has($property)) {
                    $properties->add($property);
                }
            }
        }

        return $properties->toArray();
    }

    /**
     * @return array
     */
    public function setProperties(array $properties)
    {
        $this->properties = new GObjectCollection(array());
        foreach ($properties as $property) {
            $this->addProperty($property);
        }
        return $this;
    }

    /**
     * @chainable
     */
    public function addMethod(GMethod $method, $position = self::END)
    {
        $this->methods->add($method, $position);
        $method->setGClass($this);
        return $this;
    }

    /**
     * @return GClass
     */
    public function getMethod($fqnOrIndex)
    {
        return $this->methods->get($fqnOrIndex);
    }

    /**
     *
     * notice: this tells only if the class has an own method (not the hierarchy)
     * @return bool
     */
    public function hasMethod($fqnOrClass)
    {
        return $this->methods->has($fqnOrClass);
    }

    /**
     * @chainable
     */
    public function removeMethod($fqnOrClass)
    {
        $this->methods->remove($fqnOrClass);
        return $this;
    }

    /**
     * @chainable
     */
    public function setMethodOrder($method, $position)
    {
        $this->methods->setOrder($method, $position);
        return $this;
    }

    /**
     * Returns the position of the method in the class (if available)
     * @return integer|false
     */
    public function getMethodOrder($method)
    {
        return $this->methods->getOrder($method);
    }

    /**
     * Returns the (own) methods of the class
     * @return array
     */
    public function getMethods()
    {
        return $this->methods->toArray();
    }

    /**
     * @return array
     */
    public function setMethods(array $methods)
    {
        $this->methods = new GObjectCollection(array());
        foreach ($methods as $method) {
            $this->addMethod($method);
        }
        return $this;
    }

    /**
     * Returns the methods of the class and the methods of all parents
     *
     * @param bitmap $types see self::WITH_*
     */
    public function getAllMethods($types = self::FULL_HIERARCHY)
    {
        if ($types & self::WITH_OWN) {
            $methods = clone $this->methods;
        } else {
            $methods = new GObjectCollection(array());
        }

        if ($types & self::WITH_PARENTS && $this->getParent() != null) {
            // treat duplicates (aka: overriden methods):
            $parentTypes = $types | self::WITH_OWN;
            foreach ($this->getParent()->getAllMethods($parentTypes) as $method) {
                if (!$methods->has($method)) {
                    $methods->add($method);
                }
            }
        }

        $interfaceTypes = 0x000000;
        if ($types & self::WITH_INTERFACE) {
            $interfaceTypes |= self::WITH_OWN;
        }
        if ($types & self::WITH_PARENTS_INTERFACES) {
            $interfaceTypes |= self::WITH_PARENTS;
        }

        foreach ($this->getAllInterfaces($interfaceTypes) as $interface) {
            //$interface->elevateClass();
            // return only own interface methods, because we already have all interfaces from wohle hierarchy
            // or without parents, if we dont like to have the parents interfaces
            foreach ($interface->getMethods() as $method) {
                if (!$methods->has($method)) {
                    $methods->add($method);
                }
            }
        }

        return $methods->toArray();
    }

    /**
     * Adds an import to the Class
     *
     * in case the class is written with a ClassWriter, these classes will be added to the file as a "use" statement
     * @param string $alias if not given the name of the class is used
     * @throws Exception when the alias (implicit or explicit) is already used (see Imports::add())
     */
    public function addImport(ClassInterface $gClass, $alias = null)
    {
        $this->ownImports->add($gClass, $alias);
        return $this;
    }

    /**
     * @return bool
     */
    public function hasImport($aliasOrGClass)
    {
        return $this->ownImports->have($aliasOrGClass);
    }

    /**
     * Removes an Import from the Class
     *
     * @param string $alias case insensitive
     */
    public function removeImport($alias)
    {
        $this->ownImports->remove($alias);
        return $this;
    }


    /**
     * Returns the Imports which are used in the code of the class
     *
     * These are all needed exports to make the code compile
     *
     * @return Webforge\Code\Generate\Imports
     */
    public function getImports($types = 0x000000)
    {
        $imports = clone $this->ownImports;

        // props
        foreach ($this->getProperties() as $property) {
            if ($property->getType() instanceof ObjectType && $property->getType()->hasClass()) {
                $imports->add(
                    new GClass($property->getType()->getClassFQN())
                ); // translate back to GClass (because this can be a simple ClassInterface - class)
            }
        }

        // methods
        foreach ($this->getMethods() as $method) {
            foreach ($method->getParameters() as $parameter) {
                if ($parameter->getType() instanceof ObjectType && $parameter->getType()->hasClass()) {
                    $imports->add(new GClass($parameter->getType()->getClassFQN())); // translate back
                }
            }
        }

        if ($types & self::WITH_EXTENDS && $this->parentClass != null) {
            $imports->add($this->parentClass);
        }

        // interfaces
        if ($types & self::WITH_INTERFACE) {
            foreach ($this->getInterfaces() as $interface) {
                $imports->add($interface);
            }
        }

        return $imports;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFQN();
    }

    /**
     * @param Webforge\Code\Generator\GClass $parent
     */
    public function setParent(GClass $parent)
    {
        $this->parentClass = $parent;
        return $this;
    }

    /**
     * @return Webforge\Code\Generator\GClass
     */
    public function getParent()
    {
        return $this->parentClass;
    }

    /**
     * @return bool
     */
    public function equals(ClassInterface $otherClass)
    {
        return $this->getFQN() === $otherClass->getFQN();
    }

    /**
     * @return bool
     */
    public function exists($autoload = true)
    {
        return class_exists($this->getFQN(), $autoload);
    }

    public function __clone()
    {
        $this->methods = clone $this->methods;
        $this->properties = clone $this->properties;
        $this->ownImports = clone $this->ownImports;
        $this->constants = clone $this->constants;
        $this->interfaces = clone $this->interfaces;
    }
}
