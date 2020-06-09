<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:12
 */

namespace Plexus\FormField;


use Plexus\Component;
use Plexus\DataType\Collection;
use Plexus\Error\FormError;
use Plexus\Service\Renderer\RenderRequest;
use Plexus\TemplateReference;
use Plexus\Validator\AbstractValidator;
use Plexus\Validator\RequiredValidator;

abstract class AbstractField implements Component {

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var bool
     */
    protected $required;

    /**
     * @var bool
     */
    protected $disabled;

    /**
     * @var Collection
     */
    protected $classes;

    /**
     * @var Collection
     */
    protected $attributes;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var Collection
     */
    protected $validators;

    /**
     * @var Collection
     */
    protected $errors;

    /**
     * @var Collection
     */
    protected $settings;

    /**
     * @var bool
     */
    protected $validation_made = false;

    /**
     * AbstractField constructor.
     * @param $id
     * @param array $settings
     */
    public function __construct($id, $settings=[]) {
        $this->id = $id;

        $this->settings = $this->buildSetting(new Collection($settings));

        $this->label = $this->settings->label;
        $this->name = ($this->settings->name !== null) ? $this->settings->name : $id;
        $this->required = (bool) $this->settings->required;
        $this->disabled = (bool) $this->settings->disabled;
        $this->classes = $this->settings->classes;
        $this->attributes = $this->settings->attributes;
        $this->value = "";
        $this->validators = new Collection();
        $this->settings->validators->each(function($i, AbstractValidator $validator) {
            $this->addValidator($validator);
        });
        $this->errors = new Collection();
    }

    /**
     * @param bool $override
     * @return bool
     */
    public function validate($override=false) {
        if ($this->validation_made && !$override) {
            return $this->errors->length() == 0;
        }
        $this->validation_made = true;
        return $this->_validate();
    }

    /**
     * @return bool
     */
    private function _validate() {

        $this->errors = new Collection();

        if ($this->required) {
            $validator = new RequiredValidator();
            if (!$validator->validate($this->getValue())) {
                $this->errors->push($validator->getError());
                return false;
            }
        }

        $this->validators->each(function($i, AbstractValidator $validator) {
            if (!$validator->validate($this->getValue())) {
                $this->errors->push($validator->getError());
                return !$validator->getStopValidation();
            }
            return true;
        });

        return ($this->errors->length() == 0);
    }

    /**
     * @param array $options
     * @return \Plexus\TemplateReference|string
     */
    public function render($options=[]) {
        if ($this->settings->template !== null) {
            return new RenderRequest($this->settings->template, ['field' => $this, 'options' => $options]);
        }
        return $this->_render($options);
    }

    /**
     * @param array $options
     * @return string
     */
    protected function _render($options=[]) {
        $options = new Collection($options);

        $output = "";
        if ($options->get('render_label', true)) {
            $output .= $this->renderLabel();
        }
        if ($this->settings->error_display === 'inline') {
            $output .= $this->renderInlineError();
        }
        $output .= $this->renderInput($options->get('render_value', true));
        $output .= $this->renderHelpText();
        return $output;
    }

    /**
     * @return string
     */
    protected function renderClasses() {
        $classes = "";
        $this->classes->each(function($i, $value) use (&$classes) {
            $classes .= (($i > 0) ? ' ' : '').htmlspecialchars($value);
        });
        if ($this->errors->length() > 0 && $this->settings->has_error) {
            $classes .= ((strlen($classes) > 0) ? ' ' : '').'has-error';
        }
        return $classes;
    }

    /**
     * @return string
     */
    protected function renderAttributes() {
        $attributes = "";
        $this->attributes->each(function($name, $value) use (&$attributes) {
            if ($value === true) {
                $attributes .= sprintf("%s ", htmlspecialchars($name));
            } else {
                $attributes .= sprintf("%s=\"%s\" ", htmlspecialchars($name), htmlspecialchars($value));
            }
        });
        if ($this->settings->disabled) {
            $attributes .= "disabled ";
        }
        return $attributes;
    }

    /**
     * @return string
     */
    protected function renderLabel() {
        if ($this->label !== null) {
            return sprintf("<label for='%s'>%s%s</label>",
                htmlspecialchars($this->id),
                htmlspecialchars($this->label),
                $this->settings->get('required') ? ' <b>*</b>' : ''
            );
        }
        return "";
    }

    /**
     * @return string
     */
    protected function renderInlineError() {
        $errors = "";
        if ($this->errors->length() > 0) {
            if ($this->errors->length() == 1) {
                $error = $this->errors->get(0);
                if ($error->isInline()) {
                    $errors = sprintf("<p class=\"errors\">%s</p>", htmlspecialchars($this->errors->get(0)->getMessage()));
                }
            } else {
                $errors .= "<ul class=\"errors\">";
                $this->errors->each(function($i, FormError $error) use (&$errors) {
                    if ($error->isInline()) {
                        $errors .= sprintf("<li>%s</li>", htmlspecialchars($error->getMessage()));
                    }
                });
                $errors .= "</ul>";
            }
        }
        return $errors;
    }

    /**
     * @return string
     */
    public function renderHelpText() {
        $help_text = "";
        if ($this->settings->help_text !== null) {
            $help_text = sprintf("<p class=\"help-text\">%s</p>", htmlspecialchars($this->settings->help_text));
        }
        return $help_text;
    }

    /**
     * @return string
     */
    public function renderInput($withValue=true) {
        return "";
    }



    /**
     * @param Collection $settings
     * @return Collection
     */
    public function buildSetting(Collection $settings) {
        return new Collection([
            'name' => $settings->get('name', null),
            'label' => $settings->get('label'),
            'classes' => $settings->get('classes', new Collection()),
            'attributes' => $settings->get('attributes', new Collection()),
            'required' => $settings->get('required', false),
            'disabled' => $settings->get('disabled', false),
            'help_text' => $settings->get('help_text'),
            'error_display' => $settings->get('error_display', 'inline'),
            'validators' => $settings->get('validators', new Collection()),
            'has_error' => $settings->get('class_error', true),
            'template' => $settings->get('template', null),
        ]);
    }

    /**
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name) {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param $label
     * @return $this
     */
    public function setLabel($label) {
        $this->label = (string) $label;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel() {
        return $this->label;
    }

    /**
     * @param bool $required
     * @return $this
     */
    public function setRequired($required) {
        $this->required = (bool) $required;
        return $this;
    }

    /**
     * @return bool
     */
    public function getRequired() {
        return $this->required;
    }

    /**
     * @return bool
     */
    public function isDisabled() {
        return $this->disabled;
    }

    /**
     * @param $className
     * @return $this
     */
    public function addClass($className) {
        $this->classes->push($className);
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setAttribute($name, $value) {
        $this->attributes->set($name, $value);
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFormattedValue($value) {
        $this->setValue($value);
        return $this;
    }

    /**
     * @return string
     */
    public function getFormattedValue() {
        return $this->getValue();
    }

    /**
     * @return Collection
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * @param AbstractValidator $validator
     * @return $this
     */
    public function addValidator(AbstractValidator $validator) {
        $this->validators->push($validator);
        $validator->alterField($this);
        return $this;
    }

}