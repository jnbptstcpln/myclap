<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:57
 */

namespace Plexus\FormField;


class Input extends AbstractField {

    /**
     * @var string
     */
    protected $type;

    /**
     * Input constructor.
     * @param $id
     * @param string $type
     * @param array $settings
     */
    public function __construct($id, $type='text', $settings=[]) {
        parent::__construct($id, $settings);
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function renderInput($withValue=true) {
        return sprintf('<input type="%s" class="%s" id="%s" name="%s" value="%s" %s %s/>',
            htmlspecialchars($this->type),
            $this->renderClasses(),
            htmlspecialchars($this->id),
            !$this->settings->disabled ? htmlspecialchars($this->name) : '',
            $withValue ? htmlspecialchars($this->getFormattedValue()) : '',
            $this->required ? 'required' : '',
            $this->renderAttributes()
        );
    }

}