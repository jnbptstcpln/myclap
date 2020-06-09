<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\Validator;


use Plexus\Utils\RegExp;
use Plexus\Utils\Text;

class NameValidator extends AbstractValidator {


    /**
     * NameValidator constructor.
     * @param null $message
     */
    public function __construct($message=null) {
        $message = ($message !== null) ? $message : "La valeur que vous avez indiqué n'est pas valide";
        parent::__construct($message, function($value) {
            return RegExp::matches(RegExp::$NAME, Text::withoutAccent($value));
        });
    }
}