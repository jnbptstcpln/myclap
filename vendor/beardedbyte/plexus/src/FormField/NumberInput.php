<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:57
 */

namespace Plexus\FormField;



use Plexus\Validator\NumberValidator;

class NumberInput extends Input {

    public function __construct($id, $settings=[]) {
        parent::__construct($id, 'number', $settings);
        $this->addValidator(new NumberValidator());
    }

}