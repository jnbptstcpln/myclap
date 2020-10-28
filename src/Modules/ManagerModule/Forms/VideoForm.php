<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 31/12/2019
 * Time: 13:13
 */

namespace myCLAP\Modules\ManagerModule\Forms;


use myCLAP\Modules\ManagerModule\ManagerModule;
use Plexus\Container;
use Plexus\Form;
use Plexus\FormField\CSRFInput;
use Plexus\FormField\DateInput;
use Plexus\FormField\FileInput;
use Plexus\FormField\HiddenInput;
use Plexus\FormField\SelectField;
use Plexus\FormField\TextareaField;
use Plexus\FormField\TextInput;
use Plexus\Model;
use Plexus\Validator\CustomValidator;
use Plexus\Validator\FileSizeValidator;
use Plexus\Validator\LengthMaxValidator;
use Plexus\Validator\LengthRangeValidator;
use Plexus\Validator\NameValidator;

class VideoForm extends Form {

    /**
     * @var Container
     */
    protected $container;

    public function __construct($container) {

        parent::__construct("post", '');

        $this->container = $container;

        $this
            ->addField(new CSRFInput('video-form'))
            ->addField(new TextInput('name', [
                'required' => true,
                'label' => 'Nom de la vidéo',
                'validators' => [
                    new LengthMaxValidator(75)
                ]
            ]))
            ->addField(new TextareaField('description', [
                'required' => false,
                'label' => 'Description',
                'validators' => [
                    new LengthMaxValidator(1000)
                ]
            ]))
            ->addField(new DateInput('created_on', [
                'required' => true,
                'label' => 'Date de création de la vidéo',
                'attributes' => [
                    'placeholder' => 'DD/MM/YYYY'
                ],
                'help_text' => "Cette date servira de référence dans le tri par année et dans l'ordonnancement des nouveautés.",
            ]))
            ->addField(new HiddenInput('categories', [
                'label' => "La vidéos s'inscrit dans les catégories suivantes :",
                'classes' => array('fulgur-keywords_input'),
                'attributes' => array(
                    'data-url' => "/manager/categories/api/liste"
                ),
                'validators' => [
                    new LengthMaxValidator(1000),
                    new CustomValidator(function($value) {
                        $categories = json_decode($value, true);

                        // Retrieve the available categories from the database
                        $categoriesList = [];
                        $categoryManager = $this->container->getModelManager('category');
                        $_categories = $categoryManager->getAll();
                        $_categories->each(function(Model $category) use (&$categoriesList) {
                            $categoriesList[] = $category->label;
                        });

                        if (is_array($categories)) {
                            foreach ($categories as $category) {
                                if (!in_array($category, $categoriesList)) {
                                    return false;
                                }
                            }
                            return true;
                        }
                        return false;
                    }, "Seules les valeurs proposées sont valides")
                ]
            ]))
            ->addField(new SelectField('access', ManagerModule::CONTENT_ACCESS, [
                'required' => true,
                'label' => 'Contrôle d\'accès à la vidéo'
            ]))

            ->addField(new HiddenInput('thumbnail_identifier'))
            ->addField(new FileInput('thumbnail_file', [
                'label' => "Miniature de la video",
                'classes' => array('fulgur-fileinput'),
                'attributes' => array(
                    'data-identifier' => 'thumbnail_identifier'
                ),
                'help_text' => "Fichier PNG ou JPEG de 1920x1080 pixels. Si aucune miniature n'est spécifiée ici, la vidéo aura par défaut la miniature \"myCLAP\".",
                'validators' => [
                    new FileSizeValidator(intval(10e6))
                ]
            ]))
        ;
    }

}