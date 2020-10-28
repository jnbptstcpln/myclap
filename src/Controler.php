<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 25/12/2019
 * Time: 11:19
 */

namespace myCLAP;


use myCLAP\Modules\UserModule\UserModule;
use myCLAP\Services\ImageLib;
use myCLAP\Services\PlaylistList;
use myCLAP\Services\Stat;
use myCLAP\Services\VideoList;
use Plexus\Service\FileManager;
use Plexus\Service\Renderer\RendererWrapperInterface;

class Controler extends \Plexus\Controler {

    /**
     * @return \Plexus\Service\AbstractService|RendererWrapperInterface
     * @throws \Exception
     */
    public function getRenderer() {
        return $this->getContainer()->getService('Renderer');
    }

    /**
     * @return \Plexus\Module|UserModule
     * @throws \Exception
     */
    public function getUserModule() {
        return $this->getContainer()->getModule('UserModule');
    }

    /**
     * @return \Plexus\Service\AbstractService|FileManager
     * @throws \Exception
     */
    public function getFileManager() {
        return $this->getContainer()->getService('FileManager');
    }

    /**
     * @return \Plexus\Service\AbstractService|ImageLib
     * @throws \Exception
     */
    public function getImageLib() {
        return $this->getContainer()->getService('ImageLib');
    }

    /**
     * @return \Plexus\Service\AbstractService|Stat
     * @throws \Exception
     */
    public function getStat() {
        return $this->getContainer()->getService('Stat');
    }

    /**
     * @return \Plexus\Service\AbstractService|VideoList
     * @throws \Exception
     */
    public function getVideoList() {
        return $this->getContainer()->getService('VideoList');
    }

    /**
     * @return \Plexus\Service\AbstractService|PlaylistList
     * @throws \Exception
     */
    public function getPlaylistList() {
        return $this->getContainer()->getService('PlaylistList');
    }

}