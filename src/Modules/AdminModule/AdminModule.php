<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 29/12/2019
 * Time: 23:18
 */

namespace myCLAP\Modules\AdminModule;


use myCLAP\Module;
use Plexus\Exception\HttpException;
use Plexus\Session;

class AdminModule extends Module {

    /**
     * @throws HttpException
     * @throws \Exception
     */
    public function middleware() {

        Session::pushCurrentURL();

        if (!$this->getUserModule()->isConnected()) {
            $this->redirect($this->buildRouteUrl('user-login'));
            return;
        }

        if (!$this->getUserModule()->hasPermission('admin')) {
            throw HttpException::createFromCode(403);
        }
    }

}