<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 25/12/2019
 * Time: 11:19
 */

namespace myCLAP\Modules\SearchModule\Controlers;


use myCLAP\Controler;
use myCLAP\Modules\SearchModule\SearchModule;
use Plexus\Session;

class Index extends Controler {

    /**
     * @throws \Exception
     */
    public function search() {

        Session::pushCurrentURL();

        $search_value = $this->paramGet('value');
        Session::set('__search_value', $search_value);

        if (strlen($search_value) == 0) {
            $this->redirect($this->buildRouteUrl('home-index'));
            return;
        }

        $videos = $this->getSearchModule()->perform_search($search_value, 5, 0);

        $this->render('@SearchModule/index/search.html.twig', [
            'videos' => $videos
        ]);
    }

    /**
     * @return \Plexus\Module|SearchModule
     * @throws \Exception
     */
    public function getSearchModule() {
        return $this->getContainer()->getModule('SearchModule');
    }

}