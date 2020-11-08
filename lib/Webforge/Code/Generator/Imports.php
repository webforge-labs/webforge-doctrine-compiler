<?php

namespace Webforge\Code\Generator;

use ArrayIterator;
use Countable;
use Doctrine\Common\Util\Debug;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use RuntimeException;
use Webforge\Common\ClassInterface;

class Imports implements IteratorAggregate, Countable
{
    /**
     * @var array
     */
    protected $classes = array();

    /**
     * @var array the keys from $classes but lowercased
     */
    protected $aliases = array();

    /**
     * you can use numeric keys for imports without an specific alias (sets the alias to the className of the class)
     * @param array $alias => GClass $importClass
     */
    public function __construct(array $importClasses = array())
    {
        foreach ($importClasses as $alias => $gClass) {
            if (is_numeric($alias)) {
                $alias = null;
            }

            $this->add($gClass, $alias);
        }
    }

    public function php($contextNamespace, $eol = "\n")
    {
        $use = null;
        foreach ($this->classes as $alias => $import) {
            // is it needed to import the class?
            if ($import->getNamespace() === null || $import->getNamespace() !== $contextNamespace) {
                $use .= 'use ';
                if ($alias === $import->getName()) {
                    $use .= $import->getFQN();
                } else {
                    $use .= $import->getFQN() . ' AS ' . $alias;
                }

                $use .= ';' . $eol;
            }
        }
        return $use;
    }

    /**
     * Adds an Import
     *
     * its not allowed to set an already used alias.
     * You have to remove the alias first
     * @param string $alias sets an explicit alias. (the implicit is always the classname)
     */
    public function add(ClassInterface $import, $alias = null)
    {
        if (empty($alias)) {
            $alias = $import->getName();
        }

        if (empty($alias)) {
            throw new InvalidArgumentException('GClass: ' . $import . ' must have a valid FQN: ' . $import->getFQN());
        }

        if (array_key_exists($lowerAlias = mb_strtolower($alias), $this->aliases)) {
            $usedBy = $this->classes[$this->aliases[$lowerAlias]];

            if (!$import->equals($usedBy)) {
                throw new LogicException('Alias: ' . $alias . ' is already used by Class ' . $usedBy);
            }
        }

        $this->classes[$alias] = $import;
        $this->aliases[$lowerAlias] = $alias;
        return $this;
    }

    /**
     * Removes an Import
     *
     * @param string|GClass $aliasOrGClass
     * @chainable
     */
    public function remove($aliasOrGClass)
    {
        if ($aliasOrGClass instanceof ClassInterface) {
            $gClass = $aliasOrGClass;
            $alias = $aliasOrGClass->getName();
        } else {
            $gClass = null;
            $alias = $aliasOrGClass;
        }

        if (array_key_exists($loweralias = mb_strtolower($alias), $this->aliases)) {
            unset($this->classes[$this->aliases[$loweralias]]);
            unset($this->aliases[$loweralias]);
        } elseif (isset($gClass)) {
            foreach ($this->classes as $alias => $otherGClass) {
                if ($otherGClass->equals($gClass)) {
                    unset($this->aliases[mb_strtolower($alias)]);
                    unset($this->classes[$alias]);
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function have($aliasOrGClass)
    {
        $alias = $aliasOrGClass instanceof ClassInterface ? $aliasOrGClass->getName() : $aliasOrGClass;
        return array_key_exists(mb_strtolower($alias), $this->aliases);
    }

    /**
     * @return string|NULL
     */
    public function getAlias(ClassInterface $gClass)
    {
        foreach ($this->classes as $alias => $aliasedGClass) {
            if ($aliasedGClass->equals($gClass)) {
                return $alias;
            }
        }
        return null;
    }

    /**
     * @return GClass
     * @throws RuntimeException if alias is not in imports
     */
    public function get($alias)
    {
        if (array_key_exists($lowerAlias = mb_strtolower($alias), $this->aliases)) {
            return $this->classes[$this->aliases[$lowerAlias]];
        }

        throw new RuntimeException(sprintf("The import with alias '%s' cannot be found", $alias));
    }

    /**
     * Merges all Imports from a GClass to this imports
     *
     * @chainable
     */
    public function mergeFromClass(GClass $gClass)
    {
        try {
            foreach ($gClass->getImports()->toArray() as $alias => $import) {
                $this->add($import, $alias);
            }
        } catch (InvalidArgumentException $e) {
            Debug::dump($gClass->getImports()->toArray());
            throw new InvalidArgumentException(
                'Cannot add all imports from ' . $gClass->getFQN() . ' into imports.',
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * Gets an iterator for iterating over the elements in the collection.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->classes);
    }

    /**
     * Convert to array
     *
     * the keys of the array are the aliases (case sensitive, as given in add)
     * the values are the gclasses
     * @return array string $alias => GClass $gClass
     */
    public function toArray()
    {
        return $this->classes;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->classes);
    }
}
