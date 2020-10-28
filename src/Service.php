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
use Plexus\Service\FileManager;
use Plexus\Service\Renderer\RendererWrapperInterface;

class Service extends \Plexus\Service\AbstractService {

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

}