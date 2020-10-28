<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 27/12/2019
 * Time: 18:50
 */

namespace myCLAP\Modules\SearchModule;


use myCLAP\Module;
use myCLAP\Modules\ManagerModule\ManagerModule;
use Plexus\Event\ApplicationLoaded;
use Plexus\Event\EventManager;
use Plexus\Session;
use Plexus\Utils\Text;

class SearchModule extends Module {

    /**
     * @param EventManager $eventManager
     */
    protected function registrerEventListeners(EventManager $eventManager) {
        $eventManager->addEventListener(ApplicationLoaded::class, function(ApplicationLoaded $event) {
            $this->registerRendererFunctions();
        });
    }

    /**
     * @throws \Exception
     */
    protected function registerRendererFunctions() {
        $this->getRenderer()->addFunction('__search_value', function() {
            return Session::get('__search_value');
        });
    }

    /**
     * @param string $value
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws \Plexus\Exception\ModelException
     * @throws \Exception
     */
    public function perform_search($value, $limit, $offset=0) {

        $videoManager = $this->getContainer()->getModelManager('video');
        $qb = $videoManager->getQueryBuilder();

        $qb
            ->select('*')
            ->where('upload_status = 0')
            ->where('(name LIKE :value OR description LIKE :value)')
            ->order('uploaded_on', 'desc')
            ->limit($limit)
            ->offset($offset)
        ;

        // Select the access policy depending on user login
        if ($this->getUserModule()->isConnected()) {
            if ($this->getUserModule()->hasPermission('myclap.private')) {
                $qb->where(
                    Text::format(
                        '(access = "{}" OR access = "{}" OR access = "{}")',
                        ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS],
                        ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC],
                        ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PRIVATE]
                    )
                );
            } else {
                $qb->where(
                    Text::format(
                    '(access = "{}" OR access = "{}")',
                        ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS],
                        ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]
                    )
                );
            }

        } else {
            $qb->where(Text::format('(access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        }

        return $videoManager->executeQueryBuilder($qb, ['value' => Text::format("%{}%", $value)]);
    }

    /**
     * @param string $value
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws \Plexus\Exception\ModelException
     * @throws \Exception
     */
        public function perform_search_with_token($value, $limit, $offset=0) {

        $videoManager = $this->getContainer()->getModelManager('video');
        $qb = $videoManager->getQueryBuilder();

        $qb
            ->select('*')
            ->where('upload_status = 0')
            ->where('(name LIKE :value OR description LIKE :value OR token = :strict_value)')
            ->order('uploaded_on', 'desc')
            ->limit($limit)
            ->offset($offset)
        ;

        // Select the access policy depending on user login
        if ($this->getUserModule()->isConnected()) {
            if ($this->getUserModule()->hasPermission('myclap.private')) {
                $qb->where(
                    Text::format(
                        '(access = "{}" OR access = "{}" OR access = "{}" OR (access = "{}" AND token = :strict_value))',
                        ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS],
                        ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC],
                        ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PRIVATE],
                        ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_UNLINKED]
                    )
                );
            } else {
                $qb->where(
                    Text::format(
                        '(access = "{}" OR access = "{}" OR (access = "{}" AND token = :strict_value))',
                        ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS],
                        ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC],
                        ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_UNLINKED]
                    )
                );
            }

        } else {
            $qb->where(Text::format('(access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        }

        return $videoManager->executeQueryBuilder(
            $qb,
            [
                'value' => Text::format("%{}%", $value),
                'strict_value' => $value
            ]
        );
    }
}