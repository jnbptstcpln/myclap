<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/01/2020
 * Time: 16:06
 */

namespace myCLAP\Modules\ManagerModule\Forms;


use Plexus\Form;
use Plexus\FormField\CSRFInput;
use Plexus\FormField\TextareaField;
use Plexus\FormField\TextInput;

class CategoryForm extends Form {

    public function __construct() {
        parent::__construct('post');

        $this
            ->addField(new CSRFInput('category-form'))
            ->addField(new TextInput('label', [
                'required' => true,
                'label' => 'Nom de la catégorie'
            ]))
            ->addField(new TextareaField('description', [
                'required' => true,
                'label' => 'Description rapide'
            ]))
        ;
    }

}