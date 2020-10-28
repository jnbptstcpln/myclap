<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 25/12/2019
 * Time: 11:19
 */

namespace myCLAP\Modules\HomeModule\Controlers;


use myCLAP\Controler;
use Plexus\Session;

class Year extends Controler {

    /**
     * @throws \Exception
     */
    public function middleware() {
        $this->getRenderer()->addGlobal('LeftbarActive', 'years');
    }

    /**
     * @throws \Exception
     */
    public function index() {

        Session::pushCurrentURL();

        // Compute what is the first year

        $this->render('@HomeModule/year/index.html.twig');
    }

    /**
     * @param $slug
     * @throws \Exception
     */
    public function details($slug) {

        Session::pushCurrentURL();

        $this->render('@HomeModule/year/details.html.twig');
    }

}