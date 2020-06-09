<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\Validator;




use Plexus\FormField\AbstractField;

class LengthMaxValidator extends CustomValidator {

    /**
     * @var int
     */
    protected $max_length;

    /**
     * LengthMaxValidator constructor.
     * @param $max_length
     * @param null $message
     */
    public function __construct($max_length, $message=null) {
        parent::__construct(function($value) {
            return $this->max_length >= strlen((string) $value);
        }, ($message !== null) ? $message : sprintf("Cette valeur ne peut pas faire plus de %d caractère%s", $this->max_length, $this->max_length > 1 ? 's' : ''));
        $this->max_length = $max_length;
    }

    /**
     * @param AbstractField $field
     */
    public function alterField(AbstractField $field) {
        $field->setAttribute('maxlength', $this->max_length);
    }
}