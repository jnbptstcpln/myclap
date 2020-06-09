<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 23/01/2019
 * Time: 19:20
 */

namespace Plexus;




class ModelManager_MySQL extends ModelManager {

    public function get_structure_query() {
        return "DESCRIBE $this->tableName";
    }

    /**
     * @param $type
     * @return int
     */
    protected function parse_table_type($type) {
        $pattern = "/([a-zA-Z]+)\(?/";
        preg_match_all($pattern, $type, $matches, PREG_SET_ORDER, 0);
        $type = strtolower($matches[0][1]);
        switch ($type) {
            case 'time':
                return Model::$TIME;
            case 'date':
                return Model::$DATE;
            case 'datetime':
                return Model::$DATETIME;
            case 'timestamp':
            case 'year':
            case 'tinyint':
            case 'smallint';
            case 'mediumint';
            case 'int':
            case 'bigint':
                return Model::$INTEGER;
            case 'float':
            case 'double':
                return Model::$REAL;
            case 'text':
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'varchar':
            case 'blob':
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'enum':
            default:
                return Model::$STRING;
        }
    }
}