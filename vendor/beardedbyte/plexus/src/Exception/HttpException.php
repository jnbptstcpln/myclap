<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 02/08/2019
 * Time: 00:16
 */

namespace Plexus\Exception;


class HttpException extends PlexusException {

    static function createFromCode($code) {
        return new HttpException(sprintf('HTTP %d', $code), $code);
    }
}