<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\Validator;




class RequiredValidator extends AbstractValidator {

    /**
     * RequiredValidator constructor.
     */
    public function __construct() {
        parent::__construct("Vous devez remplir ce champ", function($value) {
            return strlen( (string) $value) > 0;
        }, true);
    }
}