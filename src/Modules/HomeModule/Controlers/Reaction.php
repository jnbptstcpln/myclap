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

class Reaction extends Controler {

    /**
     * @throws \Exception
     */
    public function middleware() {

        Session::pushCurrentURL();

        if (!$this->getUserModule()->isConnected()) {
            $this->redirect($this->buildRouteUrl('user-login'));
            return;
        }

        $this->getRenderer()->addGlobal('LeftbarActive', 'reactions');
    }

    /**
     * @throws \Exception
     */
    public function index() {

        $username = $this->getUserModule()->getUser()->username;
        $numberOfVideos = $this->getStat()->number_of_favorite_videos_for_user($username);
        $videos = $this->getVideoList()->user_favorite_videos($username,10);

        $this->render('@HomeModule/reaction/index.html.twig', [
            'number_of_videos' => $numberOfVideos,
            'videos' => $videos
        ]);
    }

    /**
     * @param $slug
     * @throws \Exception
     */
    public function details($slug) {
        $this->render('@HomeModule/year/details.html.twig');
    }

}