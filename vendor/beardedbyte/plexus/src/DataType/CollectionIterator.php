<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 31/07/2019
 * Time: 22:38
 */

namespace Plexus\DataType;


class CollectionIterator implements \Iterator {

    private $var = array();

    public function __construct(Collection $collection)
    {
        if (is_array($collection->getArray())) {
            $this->var = $collection->getArray();
        }
    }

    public function rewind()
    {
        reset($this->var);
    }

    public function current()
    {
        return current($this->var);
    }

    public function key()
    {
        return key($this->var);
    }

    public function next()
    {
        return next($this->var);
    }

    public function valid()
    {
        $key = key($this->var);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }
}