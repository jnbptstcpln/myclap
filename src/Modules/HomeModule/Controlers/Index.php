<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 25/12/2019
 * Time: 11:19
 */

namespace myCLAP\Modules\HomeModule\Controlers;


use myCLAP\Controler;
use myCLAP\Modules\ManagerModule\ManagerModule;
use myCLAP\Services\LocalStorage;
use Plexus\Session;
use Plexus\Utils\Text;

class Index extends Controler {

    /**
     * @throws \Exception
     */
    public function middleware() {
        $this->getRenderer()->addGlobal('LeftbarActive', 'accueil');
    }

    /**
     * @throws \Exception
     */
    public function index() {

        Session::pushCurrentURL();

        $billboard = LocalStorage::get("billboard", []);

        $this->render('@HomeModule/index/index.html.twig', [
            'recent_videos' => $this->getVideoList()->recent_videos(8, 0),
            'billboard' => $billboard
        ]);
    }

}