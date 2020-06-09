<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\Validator;




use Plexus\FormField\AbstractField;

class LengthMinValidator extends CustomValidator {

    /**
     * @var int
     */
    protected $min_length;

    /**
     * LengthMinValidator constructor.
     * @param $min_length
     * @param null $message
     */
    public function __construct($min_length, $message=null) {
        $this->min_length = $min_length;
        parent::__construct(function($value) {
            return $this->min_length <= strlen((string) $value);
        }, ($message !== null) ? $message : sprintf("Ce champ ne peut pas faire moins de %d caractère%s", $this->min_length, $this->min_length > 1 ? 's' : ''));
    }

    /**
     * @param AbstractField $field
     */
    public function alterField(AbstractField $field) {
        $field->setAttribute('minlength', $this->min_length);
    }
}