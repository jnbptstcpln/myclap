<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\Validator;




use Plexus\FormField\AbstractField;

class LengthRangeValidator extends CustomValidator {

    /**
     * @var int
     */
    protected $min_length;

    /**
     * @var int
     */
    protected $max_length;

    /**
     * LengthRangeValidator constructor.
     * @param $min_length
     * @param $max_length
     * @param null $message
     */
    public function __construct($min_length, $max_length, $message=null) {
        $this->min_length = $min_length;
        $this->max_length = $max_length;
        parent::__construct(function($value) {
            return (strlen((string) $value) >= $this->min_length && strlen((string) $value) <= $this->max_length);
        }, ($message !== null) ? $message : sprintf("Ce champ doit être compris entre %d caractère%s et %d caractère%s",
            $this->min_length,
            $this->min_length > 1 ? 's' : '',
            $this->max_length,
            $this->max_length > 1 ? 's' : ''
        ));
    }

    /**
     * @param AbstractField $field
     */
    public function alterField(AbstractField $field) {
        $field->setAttribute('maxlength', $this->max_length);
        $field->setAttribute('minlength', $this->min_length);
    }
}