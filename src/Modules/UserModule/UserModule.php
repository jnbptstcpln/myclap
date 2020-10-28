<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 27/12/2019
 * Time: 00:00
 */

namespace myCLAP\Modules\UserModule;


use myCLAP\Application;
use myCLAP\Module;
use Plexus\Event\ApplicationLoaded;
use Plexus\Event\EventManager;
use Plexus\Model;
use Plexus\Session;
use Plexus\Utils\Randomizer;
use Plexus\Utils\Text;

class UserModule extends Module {

    const PERMISSIONS = [

        'myclap.private' => [
            'identifier' => 'myclap.private',
            'description' => "Droit de pouvoir regarder les vidéos \"privées\""
        ],

        'manager.video.upload' => [
            'identifier' => 'manager.video.upload',
            'description' => "Droit de mettre en ligne des vidéos sur le site"
        ],
        'manager.video.manage' => [
            'identifier' => 'manager.video.manage',
            'description' => "Droit de pouvoir gérer toutes les vidéos présentent sur le site"
        ],
        'manager.playlist' => [
            'identifier' => 'manager.playlist',
            'description' => "Droit de pouvoir gérer les playlists présentent sur le site"
        ],
        'manager.category' => [
            'identifier' => 'manager.category',
            'description' => "Droit de pouvoir gérer les catégories disponibles sur le site"
        ],
        'manager.stat' => [
            'identifier' => 'manager.stat',
            'description' => "Droit de pouvoir accéder aux statistiques globales du site"
        ],
        'manager.design' => [
            'identifier' => 'manager.design',
            'description' => "Droit de pouvoir gérer l'affichage de la page d'accueil"
        ],

        'admin' => [
            'identifier' => 'admin',
            'description' => "Droit d'administration sur tout le site"
        ],
    ];

    /**
     * @var Model
     */
    protected $user;

    protected function registrerEventListeners(EventManager $eventManager) {
        $eventManager->addEventListener(ApplicationLoaded::class, function(ApplicationLoaded $event) {
            $this->userCkeckpoint();
            $this->registerRendererGlobals();
        });
    }

    /**
     * @throws \Exception
     */
    protected function userCkeckpoint() {
        if (Session::isset('user_id')) {
            $userManager = $this->getContainer()->getModelManager('user');
            $user = $userManager->get(Session::get('user_id'));
            if (!$user) {
                $this->closeUserSession();
            } else {
                $this->user = $user;
            }
        } elseif (isset($_COOKIE['myclap-stayconnected'])) {
            $token = $_COOKIE['myclap-stayconnected'];
            $stayManager = $this->getModelManager("user_stayconnected");
            $stay = $stayManager->select(['token' => $token], true);
            if ($stay) {
                $userManager = $this->getModelManager("user");
                $user = $userManager->select(['username' => $stay->username], true);
                if ($user) {
                    $this->openUserSession($user);
                } else {
                    $stayManager->delete($stay);
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function registerRendererGlobals() {
        $renderer = $this->getRenderer();
        $renderer->addGlobal('__UserModule', $this);
        $renderer->addGlobal('__PERMISSIONS', $this::PERMISSIONS);
    }

    /**
     * @param Model $user
     * @throws \Plexus\Exception\ModelException
     */
    public function openUserSession(Model $user) {
        $this->user = $user;
        Session::set('user_id', $user->id);

        $stayManager = $this->getModelManager("user_stayconnected");
        $stays = $stayManager->select(['username' => $user->username]);
        $stays->each(function(Model $stay) {
            $stay->getManager()->delete($stay);
        });

        try {
            $stay = $stayManager->create();
            $stay->username = $user->username;
            $stay->token = Randomizer::generate_unique_token(200, function($value) use ($stayManager) {
                return $stayManager->select(['token' => $value], true) === null;
            });
            $stayManager->insert($stay, ['created_on' => 'NOW()']);
            setcookie("myclap-stayconnected", $stay->token, time()+3600*24*30, '/');
        } catch (\Exception $e) {
            $this->log($e);
        }

    }

    /**
     *
     */
    public function closeUserSession() {
        $this->user = null;
        Session::unset('user_id');
        session_destroy();
        setcookie("myclap-stayconnected", "", time()-3600, '/');
    }

    /**
     * @param $identifier
     * @param Model|null $user
     * @return bool
     * @throws \Exception
     * @throws \Plexus\Exception\ModelException
     */
    public function hasPermission($identifier, Model $user=null) {

        $user = $user === null ? $this->getUser() : $user;

        // No user (e.g. the user is not connected) means no permission
        if ($user === null) {
            return false;
        }

        $permissionManager = $this->getContainer()->getModelManager('user_permission');
        $permission = $permissionManager->select(['username' => $user->username, 'identifier' => $identifier], true);

        if ($permission) {
            return true;
        }
        return false;
    }

    /**
     * @param $group_identifier
     * @param Model|null $user
     * @return bool
     * @throws \Exception
     * @throws \Plexus\Exception\ModelException
     */
    public function hasPermissionGroup($group_identifier, Model $user=null) {

        $identifier = Text::format("{}.%", $group_identifier);

        $user = $user === null ? $this->getUser() : $user;

        // No user (e.g. the user is not connected) means no permission
        if ($user === null) {
            return false;
        }

        $permissionManager = $this->getContainer()->getModelManager('user_permission');
        $qb = $permissionManager->getQueryBuilder();
        $qb
            ->select('*')
            ->where('identifier LIKE :identifier')
            ->where('username = :username')
        ;
        $permissions = $permissionManager->executeQueryBuilder($qb, ['username' => $user->username, 'identifier' => $identifier]);

        if (count($permissions) > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param $identifier
     * @param Model $user
     * @throws \Exception
     * @throws \Plexus\Exception\ModelException
     */
    public function addPermission($identifier, Model $user) {

        $connectedUser = $this->getUser();

        if ($connectedUser) {
            $permissionManager = $this->getContainer()->getModelManager('user_permission');
            $permission = $permissionManager->create();
            $permission->identifier = $identifier;
            $permission->username = $user->username;
            $permission->created_by = $connectedUser->username;

            $permissionManager->insert($permission, [
                'created_on' => 'NOW()'
            ]);
        }

    }

    /**
     * @return Model
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @return bool
     */
    public function isConnected() {
        return $this->user !== null;
    }

}