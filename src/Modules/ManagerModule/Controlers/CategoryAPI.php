<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 28/12/2019
 * Time: 18:44
 */

namespace myCLAP\Modules\ManagerModule\Controlers;


use myCLAP\Controler;
use myCLAP\ControlerAPI;
use Plexus\Exception\HttpException;
use Plexus\Model;

class CategoryAPI extends ControlerAPI {

    /**
     * @throws \Exception
     */
    public function middleware() {

    }

    /**
     * @throws \Exception
     */
    public function get_categories() {
        $categoryManager = $this->getModelManager('category');
        $categories = $categoryManager->getAll();

        $output = [];
        $categories->each(function(Model $category) use (&$output) {
            $output[] = [
                'label' => $category->label
            ];
        });

        $this->success($output);
    }

    /**
     * @throws \Exception
     */
    public function index() {
        $this->render('@ManagerModule/category/index.html.twig');
    }
}