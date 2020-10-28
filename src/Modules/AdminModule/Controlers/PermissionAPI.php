<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 29/12/2019
 * Time: 23:22
 */

namespace myCLAP\Modules\AdminModule\Controlers;


use myCLAP\ControlerAPI;
use myCLAP\Modules\UserModule\UserModule;
use Plexus\Utils\Text;

class PermissionAPI extends ControlerAPI {

    /**
     * @throws \Exception
     */
    public function search_user1() {
        $value = $this->paramGet('value');

        $userManager = $this->getModelManager('user');
        $qb = $userManager->getQueryBuilder();
        $qb
            ->select('*')
            ->where('first_name LIKE :value')
            ->where('last_name LIKE :value', 'OR')
            ->where('email_centrale LIKE :value', 'OR')
            ->order('email_centrale')
            ->limit(10)
        ;
        $users = $userManager->executeQueryBuilder($qb, ['value' => Text::format("%{}%", $value)]);

        $output = [];
        foreach ($users as $user) {
            $output[] = [
                'label' => Text::format("{} {}", $user['first_name'], $user['last_name']),
                'href' => $this->buildRouteUrl('admin-permission-username', $user['username'])
            ];
        }

        $this->success($output);
    }

    /**
     * @throws \Exception
     */
    public function search_user2() {
        $value = $this->paramGet('value');

        $userManager = $this->getModelManager('user');
        $qb = $userManager->getQueryBuilder();
        $qb
            ->select('*')
            ->where('first_name LIKE :value')
            ->where('last_name LIKE :value', 'OR')
            ->where('email_centrale LIKE :value', 'OR')
            ->order('email_centrale')
            ->limit(10)
        ;
        $users = $userManager->executeQueryBuilder($qb, ['value' => Text::format("%{}%", $value)]);

        $output = [];
        foreach ($users as $user) {
            $output[] = [
                'label' => Text::format("{}", $user['username']),
            ];
        }

        $this->success($output);
    }

    /**
     * @throws \Exception
     */
    public function get_permissions() {
        $output = [];
        foreach (UserModule::PERMISSIONS as $permission) {
            $output[] = [
                'label' => $permission['identifier'],
            ];
        }

        $this->success($output);;
    }

    /**
     * @throws \Exception
     */
    public function add() {
        $identifier = $this->paramPost('identifier');
        $username = $this->paramPost('username');

        if (!isset(UserModule::PERMISSIONS[$identifier])) {
            $this->error(404, Text::format("La permission '{}' n'existe pas", $identifier));
            return;
        }

        $_permission = UserModule::PERMISSIONS[$identifier];

        $userManager = $this->getModelManager('user');
        $user = $userManager->select(['username' => $username], true);

        if (!$user) {
            $this->error(404, Text::format("L'utilisateur '{}' n'existe pas", $username));
            return;
        }

        $permissionManager = $this->getModelManager('user_permission');
        $permission = $permissionManager->select(['username' => $username, 'identifier' => $_permission['identifier']], true);

        if ($permission) {
            $this->error(404, Text::format("L'utilisateur '{}' possède déjà la permission '{}'", $username, $_permission['identifier']));
            return;
        }

        try {
            $this->getUserModule()->addPermission($_permission['identifier'], $user);
        } catch (\Exception $e) {
            $this->log($e);
            $this->error(500, Text::format("L'opération n'a pas pu s'achever à cause d'une erreur. Aucune donnée n'a été modifiée."));
            return;
        }

        $permission = $permissionManager->select(['username' => $username, 'identifier' => $_permission['identifier']], true);

        $this->success([
            'identifier' => $permission->identifier,
            'username' => $permission->username,
            'created_on' => date('d/m/Y', strtotime($permission->created_on)),
            'created_by' => $permission->created_by,
        ]);
    }

    /**
     * @throws \Exception
     */
    public function remove() {
        $identifier = $this->paramPost('identifier');
        $username = $this->paramPost('username');

        if (!isset(UserModule::PERMISSIONS[$identifier])) {
            $this->error(404, Text::format("La permission '{}' n'existe pas", $identifier));
            return;
        }

        $_permission = UserModule::PERMISSIONS[$identifier];

        $userManager = $this->getModelManager('user');
        $user = $userManager->select(['username' => $username], true);

        if (!$user) {
            $this->error(404, Text::format("L'utilisateur '{}' n'existe pas", $username));
            return;
        }

        $permissionManager = $this->getModelManager('user_permission');
        $permission = $permissionManager->select(['username' => $username, 'identifier' => $_permission['identifier']], true);

        if (!$permission) {
            $this->error(404, Text::format("L'utilisateur '{}' ne possède pas la permission '{}'", $username, $_permission['identifier']));
            return;
        }

        try {
            $permissionManager->delete($permission);
        } catch (\Exception $e) {
            $this->log($e);
            $this->error(500, Text::format("L'opération n'a pas pu s'achever à cause d'une erreur. Aucune donnée n'a été modifiée."));
            return;
        }

        $this->success(null);
    }
}