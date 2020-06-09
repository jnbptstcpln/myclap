<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 03/08/2019
 * Time: 14:11
 */

namespace Plexus;


use Plexus\DataType\Collection;
use Plexus\Exception\ModelException;

class Model {

    static $INTEGER = 1;
    static $STRING = 2;
    static $REAL = 3;
    static $DATE = 4;
    static $DATETIME = 5;
    static $TIME = 6;


    /**
     * @var ModelManager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $_name;

    /**
     * @var array
     */
    protected $structure;

    /**
     * @var array
     */
    protected $content;

    /**
     * @var array
     */
    protected $extended_content;

    /**
     * Model constructor.
     * @param ModelManager $manager
     * @param array $content
     * @throws ModelException
     */
    public function __construct(ModelManager $manager, $content=[]) {
        $this->manager = $manager;
        $this->_name = $manager->getName();
        $this->_setStructure($manager->getStructure());
        $this->content = [];
        $this->extended_content = [];
        $this->build($content);
    }

    /**
     * @param $content
     * @return $this
     */
    private function build($content) {
        if ($content instanceof Model) {
            $content = $content->getContent();
        }
        $collection = new Collection($content);
        foreach ($this->structure as $column_name => $column) {
            $this->set($column_name, $collection->get($column_name));
        }
        return $this;
    }

    /**
     * @param array|Collection $content
     * @param null|array $fields
     * @return mixed
     */
    public function update($content, $fields=null) {
        $collection = new Collection($content);
        foreach ($this->structure as $column_name => $column) {
            if (is_array($fields) && !in_array($column_name, $fields)) {
                continue;
            }
            if ($collection->isset($column_name)) {
                $this->set($column_name, $collection->get($column_name));
            }
        }
        return $this;
    }

    /**
     * @param Form $form
     * @param null|array $fields
     * @return $this
     */
    public function updateFromForm(Form $form, $fields=null) {
        $this->update($form->getValues(), $fields);
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return float|int|string
     */
    private function cast($name, $value) {
        switch ($this->structure[$name]['type']) {
            case Model::$INTEGER:
                return intval($value);
            case Model::$REAL:
                return doubleval($value);
            case Model::$DATE:
            case Model::$DATETIME:
                if (strlen(strval($value)) == 0) {
                    return null;
                }
                return strval($value);
            case Model::$STRING:
            default:
                return strval($value);
        }
    }

    /**
     * @param $name
     * @return mixed
     * @throws ModelException
     */
    public function get($name) {
        if (isset($this->structure[$name])) {
            return $this->content[$name];
        } elseif (isset($this->extended_content[$name])) {
            return $this->extended_content[$name];
        } else {
            throw new ModelException(sprintf("Aucun champ nommé '%s' dans le modèle '%s'", $name, $this->name));
        }
    }

    /**
     * @param $name
     * @return mixed|null
     * @throws ModelException
     */
    public function __get($name) {
        return $this->get($name);
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function set($name, $value) {
        if (isset($this->structure[$name])) {
            $this->content[$name] = $this->cast($name, $value);
        } else {
            $this->extended_content[$name] = $value;
        }
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
     * @param $name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->content[$name]) || isset($this->extended_content[$name]);
    }

    /**
     * @param null|array $fields
     * @return array
     */
    public function getContent($fields=null) {
        if (is_array($fields)) {
            $content = [];
            foreach ($fields as $field) {
                if (isset($this->content[$field])) {
                    $content[$field] = $this->content[$field];
                }
            }
            return $content;
        }
        return $this->content;
    }

    /**
     * @param null|array $fields
     * @return array
     */
    public function toArray($fields=null) {
        if (is_array($fields)) {
            $array = [];
            foreach ($fields as $field) {
                if (isset($this->content[$field])) {
                    $array[$field] = $this->content[$field];
                } elseif (isset($this->extended_content[$field])) {
                    $array[$field] = $this->extended_content[$field];
                }
            }
            return $array;
        }
        return array_merge($this->content, $this->extended_content);
    }

    /**
     * @param $structure
     */
    private function _setStructure($structure) {
        $this->structure = [];
        foreach ($structure as $column) {
            $this->structure[$column['name']] = [
                'type' => $column['type']
            ];
        }
    }

    /**
     * @return ModelManager
     */
    public function getManager() {
        return $this->manager;
    }


}