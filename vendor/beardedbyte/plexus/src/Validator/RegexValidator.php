<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\Validator;


use Plexus\Utils\RegExp;

class RegexValidator extends AbstractValidator {


    /**
     * @var string
     */
    protected $pattern;

    /**
     * EmailValidator constructor.
     * @param $pattern
     * @param null $message
     * @param bool $stop_validation
     * @param null $display
     */
    public function __construct($pattern, $message=null, $stop_validation=false, $display=null) {
        $message = ($message !== null) ? $message : "La valeur que vous avez indiqué n'est pas valide";
        parent::__construct($message, function($value) {
            return RegExp::matches($this->pattern, $value);
        }, $stop_validation, $display);
        $this->pattern = $pattern;
    }
}