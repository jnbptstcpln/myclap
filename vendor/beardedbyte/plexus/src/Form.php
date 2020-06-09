<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 15:36
 */

namespace Plexus;


use Plexus\DataType\Collection;
use Plexus\Error\FormError;
use Plexus\FormField\AbstractField;
use Plexus\Utils\Text;

class Form implements Component {

    static $POST = 'post';
    static $GET = 'get';

    /**
     * @var Collection
     */
    protected $fields;

    /**
     * @var Collection
     */
    protected $fields_order;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var string
     */
    protected $enctype = "multipart/form-data";

    /**
     * @var Collection
     */
    protected $errors;

    /**
     * @var string
     */
    protected $submit_text;

    /**
     * @var bool
     */
    protected $autofill = true;


    /**
     * @var bool
     */
    protected $validation_made = false;

    /**
     * @var bool|null
     */
    protected $validation_result;

    /**
     * Form constructor.
     * @param $method
     * @param string $action
     * @param array $fields
     * @throws \Exception
     */
    public function __construct($method, $action="", $fields=[]) {
        $this->method = strtolower((string) $method);
        $this->action = strtolower((string) $action);
        $this->fields = new Collection();
        $this->fields_order = new Collection();
        foreach ($fields as $i => $field) {
            $this->addField($field);
        }
        $this->errors = new Collection();
        $this->submit_text = "Envoyer";
    }

    /**
     * @param bool $override
     * @return bool
     */
    public function validate($override=false) {
        if (!$this->validation_made || $override) {
            $valid = true;
            $this->fields->each(function($i, AbstractField $field) use (&$valid, $override) {
                if (!$field->validate($override)) {
                    $valid = false;
                };
            });
            $this->validation_result = $valid;
            $this->validation_made = true;
        }
        return ($this->validation_result && $this->errors->length() == 0);
    }

    /**
     * @param Model $model
     * @return $this
     */
    public function fillWithModel(Model $model) {
        $array = new Collection($model->getContent());
        $this->fields->each(function($i, AbstractField $field) use (&$array) {
            if ($array->isset($field->getName())) {
                $field->setValue($array->get($field->getName()));
            }
        });
        return $this;
    }

    /**
     * @param $array
     * @param bool $ignore_disabled
     * @return $this
     */
    public function fillWithArray($array, $ignore_disabled=false) {
        $array = new Collection($array);
        $this->fields->each(function($i, AbstractField $field) use (&$array, $ignore_disabled) {
            if (!$field->isDisabled() || $ignore_disabled) {
                $field->setFormattedValue($array->get($field->getName()));
            }
        });
        return $this;
    }

    /**
     * @return string
     */
    public function render($options=[]) {
        $options = new Collection($options);

        $errors_html = "";
        $errors = $this->getErrors();
        if ($errors->length() > 0) {
            $errors_html .= '<div>';
            if ($errors->length() > 1) {
                $errors_html .= '<ul>';
                $errors->each(function($i, FormError $error) use (&$errors_html) {
                    $errors_html .= Text::format('<li>{}</li>', $error->getMessage());
                });
                $errors_html .= '</ul>';
            } else {
                $errors_html .= Text::format("<p>{}</p>",  $this->getErrors()->get(0)->getMessage());
            }
            $errors_html .= '</div>';
        }
        $fields_html = "";
        $this->fields->each(function($i, AbstractField $field) use (&$fields_html, $options) {
            $fields_html .= Text::format("<div>{}</div>", $field->render($options->get($field->getId(), [])));
        });
        return Text::format("<form method='{}' action='{}' enctype='{}'>{}{}{}</form>",
            htmlspecialchars($this->method),
            htmlspecialchars($this->action),
            htmlspecialchars($this->enctype),
            $errors_html,
            $fields_html,
            Text::format("<div><button type='submit'>{}</button></div>", $this->submit_text)
        );
    }

    /**
     * @return Collection
     */
    public function getValues() {
        $values = new Collection();
        $this->fields->each(function($i, AbstractField $field) use (&$values) {
            $values->set($field->getName(), $field->getValue());
        });
        return $values;
    }

    /**
     * @return Collection
     */
    public function getFormattedValues() {
        $values = new Collection();
        $this->fields->each(function($i, AbstractField $field) use (&$values) {
            $values->set($field->getName(), $field->getFormattedValue());
        });
        return $values;
    }

    /**
     * @param $enctype
     * @return $this
     */
    public function setEnctype($enctype) {
        $this->enctype = (string) $enctype;
        return $this;
    }

    /**
     * @return string
     */
    public function getEnctype() {
        return $this->enctype;
    }

    /**
     * @param $method
     * @return $this
     */
    public function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @param $action
     * @return $this
     */
    public function setAction($action) {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * @param AbstractField $field
     * @return $this
     * @throws \Exception
     */
    public function addField(AbstractField $field) {
        if ($this->fields->get($field->getId()) !== null) {
            throw new \Exception(sprintf("Il y'a déjà un champ nommé '%s' dans le formulaire", $field->getId()));
        }
        if ($this->autofill) {
            $field->setValue($this->getValueOf($field->getName()));
        }
        $this->fields_order->push($field->getId());
        $this->fields->set($field->getId(), $field);
        return $this;
    }

    /**
     * @param $name
     * @return AbstractField
     * @throws \Exception
     */
    public function getField($name) {
        if ($this->fields->get($name) === null) {
            throw new \Exception(sprintf("Le formulaire ne contient aucun champ nommé '%s'", $name));
        }
        return $this->fields->get($name);
    }

    /**
     * @return Collection
     */
    public function getFields() {
        $fields = new Collection();
        $this->fields_order->each(function($i, $field_id) use ($fields) {
            $fields->push($this->fields->get($field_id));
        });
        return $fields;
    }

    /**
     * @param $message
     * @return $this
     */
    public function addError($message) {
        $this->errors->push(new FormError($message, FormError::$DISPLAY_GLOBAL));
        return $this;
    }

    /**
     * @return Collection
     */
    public function getErrors($include_inline_errors=false) {
        $errors = new Collection();
        $this->fields->each(function($i, AbstractField $field) use (&$errors, $include_inline_errors) {
            $field->getErrors()->each(function($j, FormError $error) use (&$errors, $include_inline_errors) {
                if ($error->isGlobal() || $include_inline_errors) {
                    $errors->push($error);
                }
            });
        });
        return $this->errors->mergeWith($errors);
    }

    /**
     * @param $text
     * @return $this
     */
    public function setSubmitText($text) {
        $this->submit_text = (string) $text;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubmitText() {
        return $this->submit_text;
    }

    /**
     * @param $autoFill
     * @return $this
     */
    public function setAutoFill($autoFill) {
        $this->autofill = (bool) $autoFill;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAutoFill() {
        return $this->autofill;
    }

    /**
     * @param $name
     * @return string
     */
    public function getValueOf($name) {
        switch ($this->method) {
            case Form::$POST:
                return (isset($_POST[$name])) ? $_POST[$name] : "";
            case Form::$GET:
                return (isset($_GET[$name])) ? $_GET[$name] : "";
            default:
                return "";
        }
    }

    /**
     * @param $name
     * @return AbstractField
     * @throws \Exception
     */
    public function __get($name) {
        return $this->getField($name);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __isset($name) {
        return $this->fields->isset($name);
    }

    /**
     * @return array
     */
    public function export() {
        return [
            'method' => $this->method,
            'action' => $this->action,
            'enctype' => $this->enctype,
            'errors' => $this->getErrors()->toArray(),
            'fields' => $this->fields,
            'fields_order' => $this->fields_order
        ];
    }

}