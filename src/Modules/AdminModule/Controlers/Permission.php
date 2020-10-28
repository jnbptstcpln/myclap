<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 29/12/2019
 * Time: 23:22
 */

namespace myCLAP\Modules\AdminModule\Controlers;


use myCLAP\Controler;
use myCLAP\Modules\UserModule\UserModule;
use Plexus\Exception\HttpException;
use Plexus\Model;

class Permission extends Controler {

    public function middleware() {
        $this->getRenderer()->addGlobal('LeftbarActive', 'permissions');
    }

    /**
     * @throws \Exception
     */
    public function index() {
        $this->render("@AdminModule/permission/index.html.twig");
    }

    /**
     * @param $identifier
     * @throws HttpException
     * @throws \Exception
     */
    public function identifier($identifier) {

        if (!isset(UserModule::PERMISSIONS[$identifier])) {
            throw HttpException::createFromCode(404);
        }

        $permissionManager = $this->getModelManager('user_permission');
        $permissions = $permissionManager->select(['identifier' => UserModule::PERMISSIONS[$identifier]['identifier']]);

        $this->render("@AdminModule/permission/identifier.html.twig", [
            'permission' => UserModule::PERMISSIONS[$identifier],
            'permissions' => $permissions
        ]);

    }

    /**
     * @param $username
     * @throws HttpException
     * @throws \Exception
     */
    public function username($username) {

        $userManager = $this->getModelManager('user');
        $user = $userManager->select(['username' => $username], true);

        if (!$user) {
            throw HttpException::createFromCode(404);
        }

        $permissionManager = $this->getModelManager('user_permission');
        $permissions = $permissionManager->select(['username' => $username]);

        $userPermissions = [];
        $permissions->each(function(Model $permission) use (&$userPermissions) {
            $userPermissions[$permission->identifier] = $permission;
        });

        $this->render("@AdminModule/permission/username.html.twig", [
            'user' => $user,
            'permissions' => $permissions,
            'userPermissions' => $userPermissions
        ]);
    }
}