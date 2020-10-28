<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 02/01/2020
 * Time: 21:13
 */

namespace myCLAP\Services;


use myCLAP\Modules\ManagerModule\ManagerModule;
use myCLAP\Service;
use Plexus\Utils\Text;

class PlaylistList extends Service {

    /**
     * @param $limit
     * @param $offset
     * @return array
     * @throws \Exception
     */
    public function broadcast($limit, $offset) {
        $playlistManager = $this->getContainer()->getModelManager('playlist');
        $qb = $playlistManager->getQueryBuilder();
        $qb
            ->select('*')
            ->order('created_on', 'desc')
            ->where(Text::format('type = "{}"', ManagerModule::PLAYLIST_TYPE[ManagerModule::PLAYLIST_BROADCAST]))
            ->limit($limit)
            ->offset($offset)
        ;

        // Select the access policy depending on user login
        if ($this->getUserModule()->isConnected()) {
            $qb->where(Text::format('(access = "{}" OR access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS], ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        } else {
            $qb->where(Text::format('(access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        }

        return $playlistManager->executeQueryBuilder($qb);
    }

    /**
     * @param $limit
     * @param $offset
     * @return array
     * @throws \Exception
     */
    public function classic($limit, $offset) {
        $playlistManager = $this->getContainer()->getModelManager('playlist');
        $qb = $playlistManager->getQueryBuilder();
        $qb
            ->select('*')
            ->order('created_on', 'desc')
            ->where(Text::format('type = "{}"', ManagerModule::PLAYLIST_TYPE[ManagerModule::PLAYLIST_CLASSIC]))
            ->limit($limit)
            ->offset($offset)
        ;

        // Select the access policy depending on user login
        if ($this->getUserModule()->isConnected()) {
            $qb->where(Text::format('(access = "{}" OR access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS], ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        } else {
            $qb->where(Text::format('(access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        }

        return $playlistManager->executeQueryBuilder($qb);
    }
}