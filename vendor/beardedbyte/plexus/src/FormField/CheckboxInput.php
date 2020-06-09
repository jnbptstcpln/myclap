<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:57
 */

namespace Plexus\FormField;


use Plexus\DataType\Collection;

class CheckboxInput extends Input {

    /**
     * CheckboxInput constructor.
     * @param $id
     * @param $settings
     */
    public function __construct($id, $settings=[]) {
        parent::__construct($id, 'checkbox', $settings);
    }

    /**
     * @return bool|string
     */
    public function getValue() {
        return $this->value == $this->name;
    }

    /**
     * @param $value
     * @return Input|void
     */
    public function setValue($value) {
        if (is_bool($value) || is_int($value)) {
            $this->value = (bool) $value ? $this->name : '';
        } else {
            $this->value = $value;
        }
    }

    /**
     * @param array $options
     * @return string
     */
    public function _render($options=[]) {
        $options = new Collection($options);

        $output = "";
        if ($this->settings->error_display) {
            $output .= $this->renderInlineError();
        }
        $input = sprintf('<input type="%s" class="%s" id="%s" name="%s" value="%s" %s %s %s/>',
            $this->type,
            $this->renderClasses(),
            $this->id,
            $this->name,
            $this->name,
            $this->required ? 'required' : '',
            $this->renderAttributes(),
            $this->getValue() && $options->get('render_value', true) ? 'checked' : ''
        );
        if ($options->get('render_label', true)) {
            $output .= sprintf("<label for='%s'>%s %s%s</label>", htmlspecialchars($this->id), $input, htmlspecialchars($this->label), $this->required ? ' <b>*</b>' : '');
        } else {
            $output .= $input;
        }
        $output .= $this->renderHelpText();
        return $output;
    }

}