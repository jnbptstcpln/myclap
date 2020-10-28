<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 02/01/2020
 * Time: 14:54
 */

namespace myCLAP\Modules\ManagerModule\Forms;


use myCLAP\Modules\ManagerModule\ManagerModule;
use Plexus\Form;
use Plexus\FormField\CSRFInput;
use Plexus\FormField\DateInput;
use Plexus\FormField\HiddenInput;
use Plexus\FormField\SelectField;
use Plexus\FormField\TextareaField;
use Plexus\FormField\TextInput;
use Plexus\Validator\LengthMaxValidator;
use Plexus\Validator\NameValidator;

class PlaylistForm extends Form {

    /**
     * PlaylistForm constructor.
     * @throws \Exception
     */
    public function __construct() {
        parent::__construct('post');

        $this
            ->addField(new CSRFInput('playlist-form'))
            ->addField(new TextInput('name', [
                'required' => true,
                'label' => 'Nom de la playlist',
                'validators' => [
                    new LengthMaxValidator(75)
                ]
            ]))
            ->addField(new TextareaField('description', [
                'label' => 'Description',
                'validators' => [
                    new LengthMaxValidator(1000)
                ]
            ]))
            ->addField(new SelectField('type', ManagerModule::PLAYLIST_TYPE, [
                'required' => true,
                'label' => 'Type de la playlist'
            ]))
            ->addField(new HiddenInput('videos', [
                //'label' => "Vidéos de la playlist",
            ]))
            ->addField(new SelectField('access', ManagerModule::CONTENT_ACCESS, [
                'required' => true,
                'label' => 'Contrôle d\'accès à la playlist'
            ]))
            ->addField(new DateInput('created_on', [
                'required' => true,
                'label' => 'Date de création de la playlist',
                'attributes' => [
                    'placeholder' => 'DD/MM/YYYY'
                ],
                'help_text' => "Cette date servira de référence dans l'ordonnancement des nouveautés.",
            ]))
        ;
    }

}