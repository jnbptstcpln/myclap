<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 12/04/2020
 * Time: 20:45
 */

namespace myCLAP\Services;


use myCLAP\Service;
use Plexus\Utils\Path;

class LocalStorage extends Service {

    const PATH = __DIR__."/../../local_storage";

    /**
     * @param $name
     * @return mixed|null
     */
    static public function get($name, $default=null) {
        $file_path = Path::build(self::PATH, $name).".json";
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $data = json_decode($content, true);
            if ($data !== null) {
                return $data;
            }
        }
        return $default;
    }

    /**
     * @param $name
     * @param $data
     */
    static public function set($name, $data) {
        $file_path = Path::build(self::PATH, $name).".json";
        file_put_contents($file_path, json_encode($data));
        chmod($file_path, 0777);
    }
}