<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 28/12/2019
 * Time: 18:44
 */

namespace myCLAP\Modules\ManagerModule\Controlers;


use myCLAP\Controler;
use Plexus\Exception\HttpException;
use Plexus\Session;

class Stat extends Controler {

    /**
     * @throws \Exception
     */
    public function middleware() {

        if (!$this->getUserModule()->hasPermission('manager.stat')) {
            throw HttpException::createFromCode(403);
        }

        $this->getRenderer()->addGlobal('LeftbarActive', 'stats');
    }

    /**
     * @throws \Exception
     */
    public function index() {
        $this->render('@ManagerModule/stat/index.html.twig');
    }

    public function video($token) {

    }

}