<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\Validator;


use Plexus\Utils\RegExp;

class EmailValidator extends RegexValidator {

    public function __construct() {
        parent::__construct(RegExp::$EMAIL, "L'email que vous avez indiqué n'est pas valide");
    }
}