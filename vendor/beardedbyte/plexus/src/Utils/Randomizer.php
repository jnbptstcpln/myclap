<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 24/07/2019
 * Time: 22:15
 */

namespace Plexus\Utils;


class Randomizer {

    /**
     * @param int $length
     * @return string
     */
    static function string($length) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @param $length
     * @param $check_callback
     * @param bool $allow_length_inscrease
     * @param int $limit
     * @return string
     * @throws \Exception
     */
    static function generate_unique_token($length, $check_callback, $allow_length_inscrease=false, $limit=0) {
        $token = Randomizer::string($length);
        $acc=0;
        while (!$check_callback($token)) {
            $acc++;
            if ($acc % 500 == 0 && $allow_length_inscrease) {
                $length++;
            }
            if ($limit > 0 && $acc > $limit) {
                throw new \Exception("Iteration limit (%d) reached", $limit);
            }
            $token = Randomizer::string($length);
        }
        return $token;
    }

}