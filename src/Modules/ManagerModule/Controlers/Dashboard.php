<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 28/12/2019
 * Time: 18:14
 */

namespace myCLAP\Modules\ManagerModule\Controlers;


use myCLAP\Controler;
use Plexus\Session;

class Dashboard extends Controler {

    /**
     * @throws \Exception
     */
    public function middleware() {

        $this->getRenderer()->addGlobal('LeftbarActive', 'dashboard');
    }

    /**
     * @throws \Exception
     */
    public function index() {
        $this->render('@ManagerModule/dashboard/index.html.twig');
    }

}