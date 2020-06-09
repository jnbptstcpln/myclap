<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:45
 */

namespace Plexus\Utils;


class RegExp {

    static $WORD = "\w";
    static $EMAIL = "/(^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$)/";
    static $NAME = "/^[\w\ \-]*$/";

    /**
     * @param $pattern
     * @param $value
     * @return bool
     */
    static function matches($pattern, $value) {
        return (preg_match($pattern, $value) === 1);
    }

    /**
     * @param $pattern
     * @param $value
     * @return bool
     */
    static function get($pattern, $value) {
        preg_match($pattern, $value, $matches);
        return $matches;
    }

    /**
     * @param $pattern
     * @param $replacement
     * @param $string
     * @return null|string|string[]
     */
    static function replace($pattern, $replacement, $string) {
        if (is_callable($replacement)) {
            return preg_replace_callback($pattern, $replacement, $string);
        } else {
            return preg_replace($pattern, $replacement, $string);
        }
    }

    /**
     * @param $string
     * @param string $delimiter
     * @return string
     */
    static function quote($string, $delimiter='/') {
        return preg_quote($string, $delimiter);
    }

}