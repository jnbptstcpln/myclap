<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 24/07/2019
 * Time: 22:05
 */

namespace Plexus;


use Plexus;

class Session {

    static $FLASHES_SESSION_IDENTIFIER = "Plexus.Session.flashes";
    static $CSRF_TTL = 3600; // 1 hour
    static $CSRF_SESSION_IDENTIFIER = "Plexus.Session.CSRF";
    static $HISTORY_SESSION_IDENTIFIER = "Plexus.Session.history";

    /**
     *
     */
    static function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    static function get($name, $default=null) {
        Session::startSession();
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
    }

    /**
     * @param $name
     * @param $value
     */
    static function set($name, $value) {
        Session::startSession();
        $_SESSION[$name] = $value;
    }

    /**
     * @param $name
     * @return bool
     */
    static function isset($name) {
        Session::startSession();
        return isset($_SESSION[$name]);
    }

    /**
     * @param $name
     */
    static function unset($name) {
        Session::startSession();
        unset($_SESSION[$name]);
    }


    /**
     * @param $message
     * @param string $type
     * @param array $params
     */
    static function flash($message, $type='info', $params=[]) {
        static::startSession();
        if (!isset($_SESSION[static::$FLASHES_SESSION_IDENTIFIER])) {
            $_SESSION[static::$FLASHES_SESSION_IDENTIFIER] = [];
        }
        if (!isset($_SESSION[static::$FLASHES_SESSION_IDENTIFIER][$type])) {
            $_SESSION[static::$FLASHES_SESSION_IDENTIFIER][$type] = [];
        }
        $_SESSION[static::$FLASHES_SESSION_IDENTIFIER][$type][] = ['message' => $message, 'params' => $params];
    }

    /**
     * @param null $type
     * @return array
     */
    static function flashes($type=null) {
        static::startSession();
        $flashes = [];
        if ($type === null) {
            if (isset($_SESSION[static::$FLASHES_SESSION_IDENTIFIER])) {
                $flashes = $_SESSION[static::$FLASHES_SESSION_IDENTIFIER];
                $_SESSION[static::$FLASHES_SESSION_IDENTIFIER] = [];
            }
        } else {
            if (isset($_SESSION[static::$FLASHES_SESSION_IDENTIFIER]) && isset($_SESSION[static::$FLASHES_SESSION_IDENTIFIER][$type])) {
                $flashes = $_SESSION[static::$FLASHES_SESSION_IDENTIFIER][$type];
                unset($_SESSION[static::$FLASHES_SESSION_IDENTIFIER][$type]);
            }
        }
        return $flashes;
    }

    /**
     * @param $identifier
     * @param $token
     * @return bool
     */
    static function check_crsf_token($identifier, $token) {
        static::startSession();
        if (!isset($_SESSION[static::$CSRF_SESSION_IDENTIFIER])) {
            $_SESSION[static::$CSRF_SESSION_IDENTIFIER] = [];
        }
        foreach ($_SESSION[static::$CSRF_SESSION_IDENTIFIER] as $i => $csrf) {
            // Firstly clean the token
            if (time() - $csrf['timestamp'] > static::$CSRF_TTL) {
                $_SESSION[static::$CSRF_SESSION_IDENTIFIER] = array_slice($_SESSION[static::$CSRF_SESSION_IDENTIFIER], $i, 1);
                continue;
            }
            if ($token == $csrf['token'] && $identifier == $csrf['identifier']) {
                if (!$csrf['multiple_use']) {
                    $_SESSION[static::$CSRF_SESSION_IDENTIFIER] = array_slice($_SESSION[static::$CSRF_SESSION_IDENTIFIER], $i, 1);
                }
                return true;
            }
        }

        return false;

    }

    /**
     * @param $identifier
     * @param bool $multiple_use
     * @return string
     */
    static function prepare_csrf_token($identifier, $multiple_use=false) {
        static::startSession();
        if (!isset($_SESSION[static::$CSRF_SESSION_IDENTIFIER])) {
            $_SESSION[static::$CSRF_SESSION_IDENTIFIER] = [];
        }
        $token = Plexus\Utils\Randomizer::string(30);
        $_SESSION[static::$CSRF_SESSION_IDENTIFIER][] = [
            'timestamp' => time(),
            'identifier' => $identifier,
            'multiple_use' => $multiple_use,
            'token' => $token
        ];
        return $token;
    }

    /**
     * @description Push the current URL to a session stored array
     * @param bool $with_host
     */
    static function pushCurrentURL($with_host=false) {
        // Create the array
        if (!isset($_SESSION[Session::$HISTORY_SESSION_IDENTIFIER])) {
            $_SESSION[Session::$HISTORY_SESSION_IDENTIFIER] = [];
        }

        $current_url = $_SERVER["REQUEST_URI"];
        if ($with_host) {
            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$current_url;
        }

        if (Session::getLastURL() != $current_url) {
            // This array keeps the 10 last values
            if (count($_SESSION[Session::$HISTORY_SESSION_IDENTIFIER]) > 9) {
                $_SESSION[Session::$HISTORY_SESSION_IDENTIFIER] = array_slice($_SESSION[Session::$HISTORY_SESSION_IDENTIFIER], -9);
            }
            // Add the current url
            $_SESSION[Session::$HISTORY_SESSION_IDENTIFIER][] = $current_url;
        }
    }

    /**
     * @param $url
     */
    static function pushUrl($url) {
        if (Session::getLastURL() != $url) {

            // Create the array if it doesn't exist
            if (!isset($_SESSION[Session::$HISTORY_SESSION_IDENTIFIER])) {
                $_SESSION[Session::$HISTORY_SESSION_IDENTIFIER] = [];
            }

            // This array keeps the 10 last values
            if (count($_SESSION[Session::$HISTORY_SESSION_IDENTIFIER]) > 9) {
                $_SESSION[Session::$HISTORY_SESSION_IDENTIFIER] = array_slice($_SESSION[Session::$HISTORY_SESSION_IDENTIFIER], -9);
            }
            // Add the current url
            $_SESSION[Session::$HISTORY_SESSION_IDENTIFIER][] = $url;
        }
    }

    /**
     * @description Returns the last url stored in the session history
     * @return string
     */
    static function getLastURL() {
        if (!isset($_SESSION[Session::$HISTORY_SESSION_IDENTIFIER]) || count($_SESSION[Session::$HISTORY_SESSION_IDENTIFIER]) == 0) {
            return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        }
        return $_SESSION[Session::$HISTORY_SESSION_IDENTIFIER][count($_SESSION[Session::$HISTORY_SESSION_IDENTIFIER])-1];
    }



}