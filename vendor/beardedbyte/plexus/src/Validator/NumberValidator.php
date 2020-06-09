<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\Validator;




class NumberValidator extends RegexValidator {

    public function __construct() {
        parent::__construct("/\d*/", "Le nombre que vous avez indiqué n'est pas valide");
    }
}