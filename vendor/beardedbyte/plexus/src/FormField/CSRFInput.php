<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:57
 */

namespace Plexus\FormField;


use Plexus\DataType\Collection;
use Plexus\Session;

class CSRFInput extends Input {

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var bool
     */
    private $multiple_use;

    /**
     * CSRFInput constructor.
     * @param $identifier
     * @param bool $multiple_use
     * @param string $id
     * @param string $name
     * @param array $settings
     * @throws \TypeError
     */
        public function __construct($identifier, $multiple_use=false, $id="csrf_token", $name="__CSRF_TOKEN", $settings=[]) {
        parent::__construct($id, 'hidden', Collection::merge($settings, ['name' => $name]));
        $this->identifier = $identifier;
        $this->multiple_use = $multiple_use;
        $this->addValidator(new \Plexus\Validator\CSRFValidator($identifier));
    }

    /**
     * @param array $options
     * @return string
     */
    protected function _render($options=[]) {
        $options = new Collection($options);

        return sprintf('<input type="%s" class="%s" id="%s" name="%s" value="%s" %s/>',
            $this->type,
            $this->renderClasses(),
            $this->id,
            $this->name,
            ($options->get('render_value', true)) ? Session::prepare_csrf_token($this->identifier, $this->multiple_use) : '',
            $this->renderAttributes()
        );
    }

}