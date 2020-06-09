<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 31/07/2019
 * Time: 22:30
 */

namespace Plexus\DataType;

class Collection implements \IteratorAggregate {

    /**
     * @var array
     */
    protected $array;

    /**
     * Collection constructor.
     * @param array $array
     */
    public function __construct($array = []) {
        if ($array === null) {
            $array = [];
        }
        $this->setArray($array);
    }

    /**
     * @param $array Collection|array
     */
    public function setArray($array) {
        if (is_array($array)) {
            $this->array = $array;
        } else if (get_class($array) === Collection::class) {
            $this->array = $array->getArray();
        } else {
            throw new \TypeError('Please provide a valid a array');
        }

    }

    /**
     * @return array
     */
    public function getArray() {
        return $this->array;
    }

    /**
     * @return array
     */
    public function toArray() {
        return $this->array;
    }

    /**
     * @param $key
     * @param null $default
     * @param bool $array_as_collection
     * @return mixed|null|Collection
     */
    public function get($key, $default=null, $array_as_collection=true) {
        if (isset($this->array[$key])) {
            if (is_array($this->array[$key]) && $array_as_collection) {
                return new Collection($this->array[$key]);
            }
            return $this->array[$key];
        }
        return $default;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name) {
        return $this->get($name);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value) {
        $this->array[$key] = $value;
        return $this;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {
        $this->set($name, $value);
    }

    /**
     * @param $value
     * @return $this
     */
    public function push($value) {
        $this->array[] = $value;
        return $this;
    }

    /**
     * @param $callable
     * @param bool $array_as_collection
     */
    public function each($callable, $array_as_collection=true) {
        foreach ($this->array as $key => $value) {
            if (is_array($value) && $array_as_collection) {
                $value = new Collection($value);
            }
            if ($callable($key, $value) === false) {
                break;
            }
        }
    }

    /**
     * @return int
     */
    public function length() {
        return count($this->array);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function isset($name) {
        return isset($this->array[$name]);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->array[$name]);
    }

    /**
     * @param $name
     */
    public function __unset($name) {
        unset($this->array[$name]);
    }

    /**
     * @param Collection $collection
     * @return Collection
     */
    public function mergeWith(Collection $collection) {
        return new Collection(array_merge($this->getArray(), $collection->getArray()));
    }

    /**
     * @param array|Collection ...$arrays
     * @return Collection
     */
    static public function merge(...$arrays) {
        $to_merge = [];
        foreach ($arrays as $array) {
            if (is_array($array)) {
                $to_merge[] = $array;
            } elseif (get_class($array) === Collection::class) {
                $to_merge[] = $array->toArray();
            } else {
                throw new \TypeError("Please provide array or Collection as argument to Collection::merge");
            }
        }
        return new Collection(array_merge(...$to_merge));
    }

    /**
     * @return CollectionIterator|\Traversable
     */
    public function getIterator() {
        return new CollectionIterator($this);
    }

}