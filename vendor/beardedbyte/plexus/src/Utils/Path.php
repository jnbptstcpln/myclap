<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 00:28
 */

namespace Plexus\Utils;


class Path {

    /**
     * Remove the trailing "/"
     *
     * @param $path
     * @return bool|string
     */
    static public function normalize($path) {
        if (strlen($path) <= 1) {
            return $path;
        }
        if ($path[strlen($path)-1] == '/') {
            return substr($path, 0, strlen($path)-1);
        }
        return $path;
    }

    /**
     * Perform realpath
     *
     * @param $path
     * @return bool|string
     * @throws \Exception
     */
    static public function absolute($path) {
        $_path = realpath($path);
        if ($_path === false) {
            throw new \Exception(sprintf("The path '%s' doesn't exist", $path));
        }
        return $_path;
    }

    /**
     * Build a path from given arguments
     *
     * @param mixed ...$parts
     * @return bool|string
     */
    static public function build(...$parts) {
        $path = Path::normalize(array_shift($parts));
        foreach ($parts as $i => $part) {
            $path .= (($i == 0 && strlen($path) == 0) ?  '' : DIRECTORY_SEPARATOR ).Path::normalize($part);
        }
        return Path::normalize($path);
    }

}