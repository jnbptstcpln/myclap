<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:26
 */

namespace Plexus\Validator;


use Plexus\Error\FormError;
use Plexus\FormField\AbstractField;

abstract class AbstractValidator {

    /**
     * @var callable
     */
    protected $validator_function;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var bool
     */
    protected $stop_validation;

    /**
     * @var int
     */
    protected $display;

    /**
     * AbstractValidator constructor.
     * @param $message
     * @param $validator_function
     * @param bool $stop_validation
     * @param null $display
     */
    public function __construct($message, $validator_function, $stop_validation=false, $display=null) {
        $this->message = $message;
        $this->validator_function = $validator_function;
        $this->stop_validation = $stop_validation;
        $this->display = $display;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function validate($value) {
        return (bool) ($this->validator_function)($value);
    }

    /**
     * @param AbstractField $field
     */
    public function alterField(AbstractField $field) {

    }

    /**
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @return FormError
     */
    public function getError() {
        return new FormError($this->message, $this->display);
    }

    /**
     * @return bool
     */
    public function getStopValidation() {
        return $this->stop_validation;
    }

}