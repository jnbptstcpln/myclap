<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 28/12/2019
 * Time: 18:44
 */

namespace myCLAP\Modules\ManagerModule\Controlers;


use myCLAP\Controler;
use myCLAP\Services\LocalStorage;
use Plexus\Exception\HttpException;
use Plexus\Form;
use Plexus\FormField\CSRFInput;
use Plexus\FormField\SelectField;
use Plexus\FormField\TextInput;
use Plexus\Utils\Randomizer;
use Plexus\Validator\LengthMaxValidator;

class Design extends Controler {

    /**
     * @throws \Exception
     */
    public function middleware() {

        if (!$this->getUserModule()->hasPermission('manager.design')) {
            throw HttpException::createFromCode(403);
        }

        $this->getRenderer()->addGlobal('LeftbarActive', 'design');
    }

    /**
     * @throws \Exception
     */
    public function index() {

        $billboard = LocalStorage::get("billboard", []);

        $this->render('@ManagerModule/design/index.html.twig', [
            'billboard' => $billboard
        ]);
    }

    public function billboard_add() {

        $billboard = LocalStorage::get("billboard", []);

        if (count($billboard) >= 5) {
            $this->flash("Il ne peut y avoir que maximum 5 annonces", "error");
            $this->redirect($this->buildRouteUrl("manager-design-index"));
            return;
        }

        $form = new Form("post");
        $form
            ->addField(new CSRFInput("billboard-ad"))
            ->addField(new TextInput("title", [
                'label' => "Titre de l'encart",
                'required' => true,
                "validators" => [
                    new LengthMaxValidator(60)
                ]
            ]))
            ->addField(new TextInput("button", [
                'label' => "Bouton",
                'required' => true,
                "validators" => [
                    new LengthMaxValidator(30)
                ]
            ]))
            ->addField(new TextInput("url", [
                'label' => "Lien de l'encart",
                'required' => true,
                'attributes' => [
                    'placeholder' => "https://my.le-clap.fr/xxxxxx",
                ],
                "validators" => [
                    new LengthMaxValidator(255)
                ]
            ]))
            ->addField(new TextInput("icon", [
                'label' => "Icone",
                'help_text' => "Nom de l'icône du type 'fa-xxxxx', voir https://fontawesome.com/icons?d=gallery",
                'attributes' => [
                    'placeholder' => "fa-popcorn",
                ],
                "validators" => [
                    new LengthMaxValidator(30)
                ]
            ]))
            ->addField(new SelectField("color", [
                "gradient-dark-red" => "Rouge",
                "gradient-calm-darya" => "Bleu/Violet",
                "gradient-purple-dream" => "Violet",
                "gradient-sexy-blue" => "Bleu",
                "gradient-emerald-water" => "Vert/Bleu",

            ], [
                'label' => "Couleur du fond"
            ]))
        ;

        if ($this->method("post")) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {
                $data = [
                    'identifier' => "",
                    'title' => $form->getValueOf('title'),
                    'button' => $form->getValueOf('button'),
                    'url' => $form->getValueOf('url'),
                    'icon' => $form->getValueOf('icon'),
                    'color' => $form->getValueOf('color')
                ];

                // Generate a unique identifier
                $data['identifier'] = Randomizer::generate_unique_token(20, function ($value) use ($billboard) {
                    foreach ($billboard as $data) {
                        if ($data["identifier"] == $value) {
                            return false;
                        }
                    }
                    return true;
                });

                $billboard[] = $data;

                LocalStorage::set("billboard", $billboard);
                $this->flash("L'annonce a bien été ajoutée", "success");
                $this->redirect($this->buildRouteUrl("manager-design-index"));
                return;
            }
        }

        $this->render("@ManagerModule/design/billboard/add.html.twig", [
            'form' => $form
        ]);

    }

    public function billboard_edit($identifier) {

        $billboard = LocalStorage::get("billboard", []);

        foreach ($billboard as &$ad) {
            if ($ad['identifier'] == $identifier) {

                $form = new Form("post");
                $form
                    ->addField(new CSRFInput("billboard-ad"))
                    ->addField(new TextInput("title", [
                        'label' => "Titre de l'encart",
                        'required' => true,
                        "validators" => [
                            new LengthMaxValidator(60)
                        ]
                    ]))
                    ->addField(new TextInput("button", [
                        'label' => "Bouton",
                        'required' => true,
                        "validators" => [
                            new LengthMaxValidator(30)
                        ]
                    ]))
                    ->addField(new TextInput("url", [
                        'label' => "Lien de l'encart",
                        'required' => true,
                        'attributes' => [
                            'placeholder' => "https://my.le-clap.fr/xxxxxx",
                        ],
                        "validators" => [
                            new LengthMaxValidator(255)
                        ]
                    ]))
                    ->addField(new TextInput("icon", [
                        'label' => "Icone",
                        'help_text' => "Nom de l'icône du type 'fa-xxxxx', voir https://fontawesome.com/icons?d=gallery",
                        'attributes' => [
                            'placeholder' => "fa-popcorn",
                        ],
                        "validators" => [
                            new LengthMaxValidator(30)
                        ]
                    ]))
                    ->addField(new SelectField("color", [
                        "gradient-dark-red" => "Rouge",
                        "gradient-calm-darya" => "Bleu/Violet",
                        "gradient-purple-dream" => "Violet",
                        "gradient-sexy-blue" => "Bleu",
                        "gradient-emerald-water" => "Vert/Bleu",

                    ], [
                        'label' => "Couleur du fond"
                    ]))
                ;
                $form->fillWithArray($ad);

                if ($this->method("post")) {
                    $form->fillWithArray($this->paramsPost());
                    if ($form->validate()) {
                        $ad['title'] = $form->getValueOf('title');
                        $ad['button'] = $form->getValueOf('button');
                        $ad['url'] = $form->getValueOf('url');
                        $ad['icon'] = $form->getValueOf('icon');
                        $ad['color'] = $form->getValueOf('color');

                        LocalStorage::set("billboard", $billboard);
                        $this->flash("Les changements ont bien été sauvegardées", "success");
                        $this->redirect($this->buildRouteUrl("manager-design-index"));
                        return;
                    }
                }

                $this->render("@ManagerModule/design/billboard/edit.html.twig", [
                    'form' => $form
                ]);
                return;

            }
        }

        throw HttpException::createFromCode(404);
    }

    public function billboard_delete($identifier) {

        $billboard = LocalStorage::get("billboard", []);

        foreach ($billboard as $index => &$ad) {
            if ($ad['identifier'] == $identifier) {

                $form = new Form("post");
                $form
                    ->addField(new CSRFInput("billboard-ad-delete"))
                ;

                if ($this->method("post")) {
                    $form->fillWithArray($this->paramsPost());
                    if ($form->validate()) {

                        array_splice($billboard, $index, 1);

                        LocalStorage::set("billboard", $billboard);
                        $this->flash("L'annonce a bien été supprimée", "info");
                        $this->redirect($this->buildRouteUrl("manager-design-index"));
                        return;
                    }
                }

                $this->render("@ManagerModule/design/billboard/delete.html.twig", [
                    'form' => $form
                ]);
                return;

            }
        }

        throw HttpException::createFromCode(404);

    }

}