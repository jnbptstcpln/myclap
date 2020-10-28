<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 25/12/2019
 * Time: 11:19
 */

namespace myCLAP;


use myCLAP\Modules\UserModule\UserModule;
use Plexus\Service\Renderer\RendererWrapperInterface;

class Module extends \Plexus\Module {

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

}