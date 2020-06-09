<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\Validator;



class CustomValidator extends AbstractValidator {

    /**
     * CustomValidator constructor.
     * @param callable $function
     * @param $message
     * @param bool $stop_validation
     * @param null $display
     */
    public function __construct(callable $function, $message, $stop_validation=false, $display=null) {
        parent::__construct($message, $function, $stop_validation, $display);
    }
}