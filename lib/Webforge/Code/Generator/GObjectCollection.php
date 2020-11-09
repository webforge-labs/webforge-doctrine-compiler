<?php

namespace Webforge\Code\Generator;

use RuntimeException;
use Webforge\Common\ArrayUtil as A;

/**
 * A specialized, simple collection for ordered Objects
 *
 * its used in GClass for
 *  - properties
 *  - constants
 *  - methods
 *  - interfaces
 */
class GObjectCollection
{
    public const END = A::END;

    /**
     * @var GObject[] key is the GObject::getKey()
     */
    protected $objects = array();

    /**
     * @var GObject numerical keys
     */
    protected $ordered = null;

    /**
     * @var string[] key is the GObject::getKey()
     */
    protected $order = array();

    /**
     *
     * @param array $objects
     */
    public function __construct(array $objects)
    {
        foreach ($objects as $object) {
            $this->add($object);
        }
    }

    /**
     * @chainable
     */
    public function add(GObject $object, $position = self::END)
    {
        $this->objects[$key = $object->getKey()] = $object;

        A::insert($this->order, $key, $position);

        $this->ordered = null; // reset cache

        return $this;
    }

    /**
     * @return bool
     */
    public function has($objectOrKey)
    {
        $key = $objectOrKey instanceof GObject ? $objectOrKey->getKey() : $objectOrKey;
        return array_key_exists($key, $this->objects);
    }

    /**
     * @return GObject
     * @throws RuntimeException when key or index is not in collection
     */
    public function get($keyOrIndex)
    {
        if (array_key_exists($keyOrIndex, $this->objects)) {
            return $this->objects[$keyOrIndex];
        } elseif (is_numeric($keyOrIndex) && array_key_exists($keyOrIndex, $this->order)) {
            return $this->objects[$this->order[$keyOrIndex]];
        } else {
            throw new RuntimeException(sprintf("Object with key or index: '%s' cannot be found", $keyOrIndex));
        }
    }

    /**
     * @chainable
     */
    public function remove($objectOrKey)
    {
        $key = $objectOrKey instanceof GObject ? $objectOrKey->getKey() : $objectOrKey;

        if (array_key_exists($key, $this->objects)) {
            unset($this->objects[$key]);
            A::remove($this->order, $key);

            if (isset($this->ordered)) {
                array_pop($this->ordered);
            }
        }

        return $this;
    }

    /**
     * Returns the position of the object in the collection
     * @param string|GObject $objectOrKey
     * @return integer|FALSE
     */
    public function getOrder($objectOrKey)
    {
        $key = $objectOrKey instanceof GObject ? $objectOrKey->getKey() : $objectOrKey;
        return array_search($key, $this->order);
    }

    /**
     * @chainable
     * @param int $order 0-based
     */
    public function setOrder($objectOrKey, $order)
    {
        $key = $objectOrKey instanceof GObject ? $objectOrKey->getKey() : $objectOrKey;

        if (array_key_exists($key, $this->objects)) {
            $oldOrder = array_search($key, $this->order);

            if ($oldOrder != $order || $order === self::END) {
                // its not that easy as removing and inserting into the $this->order array
                // i always thought doubly linked lists would help with that but spls php does not
                A::insert($this->order, $key, $order);

                /*
                  $oldOrder = 0;
                  <
                  $order = 2;

                  array(1,2,3,1)
                  => remove $oldOrder
                */

                /*
                  setOrder('object 3', 0)
                  $oldOrder = 2;
                  <
                  $order = 0;


                  array(3,1,2,3)
                  => remove $oldOrder+1
                */

                /*
                  array('object 2', 'object 1')
                  ->setOrder('object 2', 1)
                  $oldOrder = 0;
                  <
                  $order = 1;

                  array('object 2', 'object 2', 'object 1')
                  => remove $oldOrder
                */

                /*
                  array('object 2', 'object 1')
                  ->setOrder('object 2', END)
                  $oldOrder = 0;
                  <
                  $order = END (2);

                  array('object 2', 'object 1', 'object 2')
                  => remove $oldOrder
                */

                // now key is twice in the list, but we like to remove the old one (by $order)
                array_splice($this->order, $order === self::END || $oldOrder < $order ? $oldOrder : $oldOrder + 1, 1);

                $this->ordered = null;
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if (!isset($this->ordered)) {
            $this->ordered = array();
            foreach ($this->order as $key) {
                $this->ordered[] = $this->objects[$key];
            }
        }

        return $this->ordered;
    }
}
