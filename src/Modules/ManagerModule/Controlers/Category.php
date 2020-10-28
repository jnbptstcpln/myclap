<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 28/12/2019
 * Time: 18:44
 */

namespace myCLAP\Modules\ManagerModule\Controlers;


use myCLAP\Controler;
use myCLAP\Modules\ManagerModule\Forms\CategoryForm;
use Plexus\Exception\HttpException;
use Plexus\Session;
use Plexus\Utils\Text;

class Category extends Controler {

    /**
     * @throws \Exception
     */
    public function middleware() {

        if (!$this->getUserModule()->hasPermission('manager.category')) {
            throw HttpException::createFromCode(403);
        }

        $this->getRenderer()->addGlobal('LeftbarActive', 'categories');
    }

    /**
     * @throws \Exception
     */
    public function index() {

        $categoryManager = $this->getModelManager('category');
        $qb = $categoryManager->getQueryBuilder();
        $qb
            ->select('*')
            ->order('label')
        ;
        $categories = $categoryManager->executeQueryBuilder($qb);


        $this->render('@ManagerModule/category/index.html.twig', [
            'categories' => $categories
        ]);
    }

    /**
     * @throws \Exception
     */
    public function create() {

        $categoryManager = $this->getModelManager('category');
        $form = new CategoryForm();

        if ($this->method('post')) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {
                $category = $categoryManager->create();
                $category->updateFromForm($form);

                // Generate the slug
                $slug_base = Text::slug($category->label);
                $slug = $slug_base;
                $acc = 1;
                while ($categoryManager->select(['slug' => $slug], true)) {
                    $acc += 1;
                    $slug = $slug_base.'-'.$acc;
                }
                $category->slug = $slug;

                $category->created_by = $this->getUserModule()->getUser()->username;

                try {
                    $categoryManager->insert($category, [
                        'created_on' => 'NOW()'
                    ]);
                    $this->flash("La catégorie a été ajoutée avec succès", 'success');
                    $this->redirect($this->buildRouteUrl('manager-category-index'));
                    return;
                } catch (\Exception $e) {
                    $this->log($e, 'category');
                    $form->addError("Une erreur a eu lieu lors de la mise à jour de la base de données. La catégorie n'a pas été créée.");
                }
            }
        }

        $this->render('@ManagerModule/category/create.html.twig', [
            'form' => $form
        ]);
    }

    /**
     * @throws \Exception
     */
    public function edit($slug) {

        $categoryManager = $this->getModelManager('category');
        $category = $categoryManager->select(['slug' => $slug], true);

        if (!$category) {
            throw HttpException::createFromCode(404);
        }

        $form = new CategoryForm();
        $form->fillWithModel($category);

        if ($this->method('post')) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {

                $category->updateFromForm($form);

                $category->created_by = $this->getUserModule()->getUser()->username;

                try {
                    $categoryManager->update($category);
                    $this->flash("Les changements ont bien été sauvegardés", 'success');
                    $this->refresh();
                    return;
                } catch (\Exception $e) {
                    $this->log($e, 'category');
                    $form->addError("Une erreur a eu lieu lors de la mise à jour de la base de données. Les changements n'ont pas été sauvegardés.");
                }
            }
        }

        $this->render('@ManagerModule/category/edit.html.twig', [
            'category' => $category,
            'form' => $form
        ]);
    }

    public function delete() {

    }
}