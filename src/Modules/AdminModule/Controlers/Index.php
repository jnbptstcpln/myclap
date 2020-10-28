<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 29/12/2019
 * Time: 23:22
 */

namespace myCLAP\Modules\AdminModule\Controlers;


use myCLAP\Controler;

class Index extends Controler {

    /**
     * @throws \Exception
     */
    public function index() {
        $this->render("@AdminModule/index/index.html.twig");
    }
}