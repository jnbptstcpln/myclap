<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\Validator;




use Plexus\FormField\AbstractField;

class RangeValidator extends CustomValidator {

    /**
     * @var int
     */
    protected $min;

    /**
     * @var int
     */
    protected $max;

    public function __construct($min, $max, $message=null) {
        parent::__construct(function($value) {
            return (intval($value) >= $this->min && intval($value) <= $this->max);
        }, ($message !== null) ? $message : sprintf("La valeur de ce champ doit être comprise entre %d et %d", $min, $max));
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * @param AbstractField $field
     */
    public function alterField(AbstractField $field) {
        $field->setAttribute('min', $this->min);
        $field->setAttribute('max', $this->max);
    }
}