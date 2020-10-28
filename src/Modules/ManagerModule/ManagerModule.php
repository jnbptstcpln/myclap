<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 28/12/2019
 * Time: 18:13
 */

namespace myCLAP\Modules\ManagerModule;


use myCLAP\Module;
use Plexus\Exception\HttpException;
use Plexus\Session;

class ManagerModule extends Module {

    const CONTENT_ACCESS_PRIVATE = 0;
    const CONTENT_ACCESS_UNLINKED = 1;
    const CONTENT_ACCESS_CENTRALIENS = 2;
    const CONTENT_ACCESS_PUBLIC = 3;

    const CONTENT_ACCESS = [
        ManagerModule::CONTENT_ACCESS_PRIVATE => 'Privée',
        ManagerModule::CONTENT_ACCESS_UNLINKED => 'Non répertoriée',
        ManagerModule::CONTENT_ACCESS_CENTRALIENS => 'Centraliens',
        ManagerModule::CONTENT_ACCESS_PUBLIC => 'Public'
    ];

    const PLAYLIST_CLASSIC = 0;
    const PLAYLIST_BROADCAST = 1;

    const PLAYLIST_TYPE = [
        ManagerModule::PLAYLIST_CLASSIC => "Classique",
        ManagerModule::PLAYLIST_BROADCAST => "Diffusion"
    ];

    const UPLOAD_STATUS = [
        "UPLOAD_NULL" => 2,
        "UPLOAD_INIT" => 1,
        "UPLOAD_END" => 0,
    ];

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

        if (
            !$this->getUserModule()->hasPermission('admin')
            && !$this->getUserModule()->hasPermissionGroup('manager')
        ) {
            throw HttpException::createFromCode(403);
        }

    }

}