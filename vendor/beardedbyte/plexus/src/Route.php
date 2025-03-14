<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 01:40
 */

namespace Plexus;


use Plexus\DataType\Collection;

class Route {

    static $GET = 1;
    static $POST = 2;
    static $PUT = 4;
    static $DELETE = 4;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var string
     */
    protected $path_pattern;

    /**
     * @var array
     */
    protected $path_components;

    /**
     * @var bool
     */
    protected $match_all_patterns = false;

    /**
     * @var callable
     */
    protected $action;


    /**
     * Route constructor.
     * @param $name
     * @param $method
     * @param $path_pattern
     * @param $action
     * @throws \Exception
     */
    public function __construct($name, $method, $path_pattern, $action) {
        $this->name = (string) $name;
        $this->method = 0;

        $method = strtoupper($method);
        foreach (['GET' => Route::$GET, 'POST' => Route::$POST, 'PUT' => Route::$PUT, 'DELETE' => Route::$DELETE, '*' => Route::$GET+Route::$POST+Route::$PUT+Route::$DELETE] as $method_name => $value) {
            if (strpos($method, $method_name) !== false) {
                $this->method += $value;
            }
        }

        $this->action = $action;

        // Handling the case where path: "*" (Match all the routes)
        if ($path_pattern == "*") {
            $this->match_all_patterns = true;
        } else {
            $this->parse_pattern($path_pattern);
        }

    }

    /**
     * @param $identifier
     * @return array
     * @throws \Exception
     */
    static public function parse_action_identifier($identifier) {
        $components = explode(':', $identifier);
        if (count($components) != 3) {
            throw new \Exception('Le format de l\'identifiant d\'action "'.$identifier.'" est invalide');
        }
        return [
            'module' => $components[0],
            'controler' => $components[1],
            'action' => $components[2]
        ];
    }

    /**
     * @param $string
     * @throws \Exception
     */
    public function parse_pattern($string) {


        $components = [];

        $matches = [];
        if (preg_match_all("/(\[([\w\*]*):(\w*)\]*)/", $string, $matches, PREG_SET_ORDER, 0)) {
            $_path = $string;
            foreach ($matches as $index => $match) {

                $placeholder = $match[1];
                $type = $match[2];
                $name = $match[3];
                $_parts = explode($placeholder, $_path, 2);

                $components[] = [
                    'type' => 'string',
                    'value' => $_parts[0]
                ];
                $_path = $_parts[1];

                switch ($type) {
                    case 'a':
                        $components[] = [
                            'type' => 'regex',
                            'name' => $name,
                            'value' => '(\w+)'
                        ];
                        break;
                    case 'i':
                        $components[] = [
                            'type' => 'regex',
                            'name' => $name,
                            'value' => '(\d+)'
                        ];
                        break;
                    case '*':
                        $components[] = [
                            'type' => 'regex',
                            'name' => $name,
                            'value' => '([^\/]+)'
                        ];
                        break;
                    case '**':
                        $components[] = [
                            'type' => 'regex',
                            'name' => $name,
                            'value' => '(.+)'
                        ];
                        break;
                    default:
                        throw new \Exception(sprintf("Aucun type ne correspond à '%s' dans la construction de la route '%s'", $placeholder, $string));
                }
            }

            if (strlen($_path) > 0) {
                $components[] = [
                    'type' => 'string',
                    'value' => $_path
                ];
            }

        } else {
            $components[] = [
                'type' => 'string',
                'value' => $string
            ];
        }

        $this->path_components = $components;

    }

    /**
     * @param $method
     * @param $string
     * @param $params
     * @return array|bool
     */
    public function matches($method, $string, &$params) {

        $methods = ['GET' => Route::$GET, 'POST' => Route::$POST, 'PUT' => Route::$PUT, 'DELETE' => Route::$DELETE, '*' => Route::$GET+Route::$POST+Route::$PUT+Route::$DELETE];

        if (($methods[strtoupper($method)] & $this->method) != $methods[strtoupper($method)]) {
            return false;
        }

        if ($this->match_all_patterns) {
            return true;
        }

        // Build the regex :
        $pattern = "";
        $matches_mapping = [];
        foreach ($this->path_components as $i => $component) {
            if ($component['type'] == 'string') {
                $pattern .= preg_quote($component['value'], '/');
            } else {
                $pattern .= $component['value'];
                $matches_mapping[] = $component['name'];
            }
        }

        $matches = [];
        if (preg_match('/^'.$pattern.'$/', $string, $matches)) {
            foreach ($matches_mapping as $i => $name) {
                $params[$name] = urldecode($matches[$i+1]);
            }
            return true;
        }

        return false;
    }

    /**
     * @param mixed ...$params
     * @return string
     * @throws \Exception
     */
    public function build_uri(...$params) {
        $params = new Collection($params);
        if ($params->length() == 1 && is_object($params->get(0)) && get_class($params->get(0)) == Collection::class) {
            $params = $params->get(0);
        }
        $uri = "";
        $index = -1;
        foreach ($this->path_components as $i => $component) {
            switch ($component['type']) {
                case 'string':
                    $uri .= $component['value'];
                    break;
                case 'regex':
                    $index += 1;
                    if ($params->isset($component['name'])) {
                        $uri .= urlencode($params->get($component['name']));
                    } elseif ($params->isset($index)) {
                        $uri .= urlencode($params->get($index));
                    } else {
                        throw new \Exception(sprintf("Vous devez fournir le paramètre '%s' dans la construction de la route '%s'", $component['name'], $this->getName()));
                    }
                    break;
            }
        }
        return $uri;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return callable
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * @return bool
     */
    public function matchesAllPattern() {
        return $this->match_all_patterns;
    }

}