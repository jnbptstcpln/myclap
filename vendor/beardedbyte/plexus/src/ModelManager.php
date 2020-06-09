<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 23/01/2019
 * Time: 19:20
 */

namespace Plexus;


use Plexus\Exception\ModelException;

class ModelManager
{
    /**
     * @var \PDO
     */
    public $database;

    /**
     * @var string
     */
    public $tableName = "";

    /**
     * @var array
     */
    public $structure = null;

    /**
     * @var bool
     */
    public $useDefaultId = true;

    /**
     * ModelManager constructor.
     * @param \PDO $database
     * @param $tableName
     * @param bool $useDefaultId
     */
    public function __construct(\PDO $database, $tableName, $useDefaultId=true) {
        $this->database = $database;
        $this->tableName = $tableName;
        $this->useDefaultId = $useDefaultId;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->tableName;
    }

    /**
     * @return array
     * @throws ModelException
     */
    public function getStructure() {
        $this->build_structure();
        return $this->structure;
    }

    /** Utils */

    /**
     * Return the structure of the table
     * @throws ModelException
     */
    protected function build_structure() {
        if ($this->structure === null) {
            try {
                $output = $this->database->query($this->get_structure_query());
                $structure = $output->fetchAll(\PDO::FETCH_ASSOC);
                $this->structure = [];
                foreach ($structure as $column) {
                    $this->structure[] = [
                        'name' => $column['Field'],
                        'type' => $this->parse_table_type($column['Type'])
                    ];
                }
            } catch (\Throwable $e) {
                throw new ModelException(sprintf("Impossible de récupérer la structure du modèle '%s'", $this->tableName));
            }
        }

    }

    /**
     * @return string
     */
    protected function get_structure_query() {
        return "PRAGMA table_info($this->tableName)";
    }

    /**
     * @param $type
     * @return int
     */
    protected function parse_table_type($type) {
        switch ($type) {
            case 'INTEGER':
                return Model::$INTEGER;
                break;
            case 'REAL':
                return Model::$REAL;
                break;
            case 'TEXT':
                return Model::$STRING;
                break;
            default:
                return Model::$STRING;
        }
    }


    /** Query Builder */

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder() {
        return new QueryBuilder($this->tableName);
    }

    /**
     * @param QueryBuilder $qb
     * @param array $data
     * @return array
     * @throws ModelException
     */
    public function executeQueryBuilder(QueryBuilder $qb, $data=[]) {
        return $this->execute($qb->query(), $data, false);
    }

    /**
     * @param $sql
     * @param array $data
     * @param bool $as_model
     * @return array|ModelCollection
     * @throws ModelException
     */
    public function execute($sql, $data=[], $as_model=true) {
        $this->build_structure();
        $request = $this->database->prepare($sql);
        if (!$request->execute($data)) {
            throw new ModelException('Une erreur est survenue');
        };
        $result = $request->fetchAll(\PDO::FETCH_ASSOC);
        if ($as_model) {
            $models = [];
            foreach ($result as $array) {
                $models[] = $this->_create($array);
            }
            return new ModelCollection($models);
        }
        return $result;
    }

    /** Requests builder */

    /**
     * @param array $replacements
     * @return string
     * @throws ModelException
     */
    protected function build_insert_request($replacements=[]) {
        $this->build_structure();
        $sql = "INSERT INTO $this->tableName VALUES(";
        $acc = 0;
        foreach ($this->structure as $column) {
            $acc += 1;
            if ($acc > 1) {
                $sql .= ",";
            }
            if (array_key_exists($column['name'], $replacements)) {
                $sql .= $replacements[$column['name']];
            } else {
                $sql .= ":".$column['name'];
            }
        }
        $sql .= ")";

        return $sql;
    }

    /**
     * @param $model
     * @return string
     * @throws ModelException
     */
    protected function build_select_request($model) {
        $this->build_structure();
        $sql = "SELECT * FROM $this->tableName";
        if (count($model) > 0) {
            $sql .= " WHERE ";
            $acc = 0;
            foreach ($this->structure as $column) {
                if (array_key_exists($column['name'], $model)) {
                    $acc += 1;
                    if ($acc > 1) {
                        $sql .= " AND ";
                    }
                    $sql .= $column['name']." = :".$column['name'];
                }
            }
        }
        return $sql;
    }

    /**
     * @return string
     * @throws ModelException
     */
    protected function build_update_request($replacements=[]) {
        $this->build_structure();
        $sql = "UPDATE $this->tableName SET ";
        $acc = 0;
        foreach ($this->structure as $column) {
            if ($column['name'] != 'id') {
                $acc += 1;
                if ($acc > 1) {
                    $sql .= ",";
                }
                if (array_key_exists($column['name'], $replacements)) {
                    $sql .= $column['name']." = ".$replacements[$column['name']];
                } else {
                    $sql .= $column['name']." = :".$column['name'];
                }

            }
        }
        $sql .= " WHERE id = :id";
        return $sql;
    }


    /** Basic requests */

    /**
     * @param $model
     * @param $replacements
     *
     * @throws ModelException
     */
    public function insert(Model $model, $replacements=[]) {

        $model_content = $model->getContent();

        $updateId = false;
        if ($this->useDefaultId) {
            $replacements['id'] = 'NULL';
            $updateId = true;
        }

        $sql = $this->build_insert_request($replacements);

        $_model = [];
        foreach ($model_content as $key => $value) {
            if (!array_key_exists($key, $replacements)) {
                $_model[$key] = $value;
            }
        }

        $request = $this->database->prepare($sql);
        if (!$request->execute($_model)) {
            throw new ModelException(sprintf("Une erreur est survenue lors de l'insertion du modèle '%s' (%s)", $this->tableName, $sql));
        };

        if ($updateId) {
            $_model = $this->get($this->database->lastInsertId($this->tableName));
            $model->update($_model->getContent());
        }
    }

    /**
     * * Model is an array with only the fields you want the search to be based on
     * e.g. $array('id' => 1)
     *
     * @param $conditions
     * @param bool $one
     * @return null|Model|ModelCollection
     * @throws ModelException
     */
    public function select($conditions, $one=false) {
        $sql = $this->build_select_request($conditions);
        $request = $this->database->prepare($sql);
        if (!$request->execute($conditions)) {
            throw new ModelException(sprintf("Une erreur est survenue lors de la sélection du modèle '%s' (%s)", $this->tableName, $sql));
        };

        $data = $request->fetchAll(\PDO::FETCH_ASSOC);

        if ($one) {
            if (count($data) > 0) {
                return $this->_create($data[0]);
            }
            return null;
        }

        $models = [];
        foreach ($data as $array) {
            $models[] = $this->_create($array);
        }
        return new ModelCollection($models);
    }

    /**
     * @param Model $model
     * @param $replacements
     * The update selection is based on the id
     *
     * @throws ModelException
     */
    public function update(Model $model, $replacements=[]) {
        $sql = $this->build_update_request($replacements);

        $model_content = $model->getContent();

        $_model = [];
        foreach ($model_content as $key => $value) {
            if (!array_key_exists($key, $replacements)) {
                $_model[$key] = $value;
            }
        }

        $request = $this->database->prepare($sql);
        if (!$request->execute($_model)) {
            throw new ModelException(sprintf("Une erreur est survenue lors de la mise à jour du modèle '%s' (%s)", $this->tableName, $sql));
        };
    }

    /**
     * @param Model $model
     * The delete selection is based on the id
     *
     * @throws ModelException
     */
    public function delete(Model $model) {

        $_model = ['id' => $model->id];

        $sql = "DELETE FROM $this->tableName WHERE id = :id";

        $request = $this->database->prepare($sql);
        if (!$request->execute($_model)) {
            throw new ModelException(sprintf("Une erreur est survenue lors de la suppression du modèle '%s' (%s)", $this->tableName, $sql));
        };
    }


    /** Get From DB */

    /**
     * @return null|Model|ModelCollection
     * @throws ModelException
     */
    public function getAll() {
        return $this->select(array());
    }

    /**
     * @param $id
     * @return null|Model
     * @throws ModelException
     */
    public function get($id) {
        $models = $this->select(array('id' => $id));

        if ($models->length() > 0) {
            return $this->_create($models->get(0));
        }

        return null;
    }

    /** Local function */

    /**
     * Create an empty model
     * @return Model
     * @throws ModelException
     */
    public function create() {
        return $this->_create();
    }

    /**
     * Create a model with the given content
     * @param array $content
     * @return Model
     * @throws ModelException
     */
    private function _create($content=[]) {
        return new Model($this, $content);
    }

}