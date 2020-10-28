<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 25/12/2019
 * Time: 11:19
 */

namespace myCLAP\Modules\HomeModule\Controlers;


use myCLAP\Controler;
use Plexus\Exception\HttpException;
use Plexus\Session;

class Category extends Controler {

    /**
     * @throws \Exception
     */
    public function middleware() {
        $this->getRenderer()->addGlobal('LeftbarActive', 'categories');
    }

    /**
     * @throws \Exception
     */
    public function index() {

        Session::pushCurrentURL();

        $categoryManager = $this->getModelManager('category');
        $qb = $categoryManager->getQueryBuilder();
        $qb
            ->select('*')
            ->order('label')
        ;
        $categories = $categoryManager->executeQueryBuilder($qb);

        foreach ($categories as &$category) {
            $category['number_of_videos'] = $this->getStat()->number_of_videos_for_category($category['label']);
            $category['videos'] = $this->getVideoList()->category_videos($category['label'],4);
        }

        $this->render('@HomeModule/category/index.html.twig', [
            'categories' => $categories
        ]);
    }

    /**
     * @param $slug
     * @throws \Exception
     */
    public function details($slug) {

        Session::pushCurrentURL();

        $categoryManager = $this->getModelManager('category');
        $category = $categoryManager->select(['slug' => $slug], true);

        if (!$category) {
            throw HttpException::createFromCode(404);
        }

        $category->number_of_videos = $this->getStat()->number_of_videos_for_category($category->label);
        $category->videos = $this->getVideoList()->category_videos($category->label,8);

        $this->render('@HomeModule/category/details.html.twig', [
            'category' => $category
        ]);
    }

}