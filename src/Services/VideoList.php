<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/01/2020
 * Time: 18:27
 */

namespace myCLAP\Services;


use myCLAP\Modules\ManagerModule\ManagerModule;
use myCLAP\Service;
use Plexus\Utils\Text;

class VideoList extends Service {

    /**
     * @param $limit
     * @param $offset
     * @return array
     * @throws \Exception
     */
    public function recent_videos($limit, $offset) {
        $videoManager = $this->getContainer()->getModelManager('video');
        $qb = $videoManager->getQueryBuilder();
        $qb
            ->select('*')
            ->where('upload_status = 0')
            ->order('created_on', 'desc')
            ->limit($limit)
            ->offset($offset)
        ;

        // Select the access policy depending on user login
        if ($this->getUserModule()->isConnected()) {
            $qb->where(Text::format('(access = "{}" OR access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS], ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        } else {
            $qb->where(Text::format('(access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        }

        return $videoManager->executeQueryBuilder($qb);
    }

    /**
     * @param $category_label
     * @param $limit
     * @param int $offset
     * @return array
     * @throws \Plexus\Exception\ModelException
     */
    public function category_videos($category_label, $limit, $offset=0) {

        $videoManager = $this->getContainer()->getModelManager('video');
        $qb = $videoManager->getQueryBuilder();
        $qb
            ->select('*')
            ->where('upload_status = 0')
            ->where('categories LIKE :label')
            ->order('created_on', 'desc')
            ->limit($limit)
            ->offset($offset)
        ;

        // Select the access policy depending on user login
        if ($this->getUserModule()->isConnected()) {
            $qb->where(Text::format('(access = "{}" OR access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS], ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        } else {
            $qb->where(Text::format('(access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        }

        return $videoManager->executeQueryBuilder($qb, ['label' => Text::format("%{}%", $category_label)]);


    }

    /**
     * @param $playlist_slug
     * @return array
     * @throws \Plexus\Exception\ModelException
     */
    public function playlist_videos($playlist_slug) {

        $playlistManager = $this->getContainer()->getModelManager('playlist');
        $playlist = $playlistManager->select(['slug' => $playlist_slug], true);

        if (!$playlist) {
            return [];
        }

        $_videos = json_decode($playlist->videos, true);
        $_videos = ($_videos === null) ? [] : $_videos;

        $videoManager = $this->getContainer()->getModelManager('video');
        $qb = $videoManager->getQueryBuilder();
        $qb
            ->select('*')
            ->where('upload_status = 0')
            ->where('token = :token')
        ;

        // Select the access policy depending on user login
        if ($this->getUserModule()->isConnected()) {
            $qb->where(Text::format('(access = "{}" OR access = "{}" OR access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS], ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC], ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_UNLINKED]));
        } else {
            $qb->where(Text::format('(access = "{}" OR access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC], ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_UNLINKED]));
        }

        $videos = [];
        foreach ($_videos as $video_token) {
            $__videos = $videoManager->executeQueryBuilder($qb, ['token' => $video_token]);
            if (count($__videos) > 0) {
                $videos[] = $__videos[0];
            }
        }

        return $videos;
    }

    /**
     * @param $category_label
     * @param $limit
     * @param int $offset
     * @return array
     * @throws \Plexus\Exception\ModelException
     */
    public function user_favorite_videos($username, $limit, $offset=0) {

        $videoManager = $this->getContainer()->getModelManager('video_reaction');
        $qb = $videoManager->getQueryBuilder();
        $qb
            ->select('*')
            ->join('video', 'video.token = video_reaction.video_token')
            ->where('video.upload_status = 0')
            ->where('video_reaction.username = :username')
            ->order('video_reaction.created_on', 'desc')
            ->limit($limit)
            ->offset($offset)
        ;

        // Select the access policy depending on user login
        if ($this->getUserModule()->isConnected()) {
            $qb->where(Text::format('(video.access = "{}" OR video.access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS], ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        } else {
            $qb->where(Text::format('(video.access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        }

        return $videoManager->executeQueryBuilder($qb, ['username' => $username]);
    }

    /**
     * @param $category_label
     * @param $limit
     * @param int $offset
     * @return array
     * @throws \Plexus\Exception\ModelException
     */
    public function year_videos($category_label, $limit, $offset=0) {

        $videoManager = $this->getContainer()->getModelManager('video');
        $qb = $videoManager->getQueryBuilder();
        $qb
            ->select('*')
            ->where('upload_status = 0')
            ->where('categories LIKE :label')
            ->order('created_on', 'desc')
            ->limit($limit)
            ->offset($offset)
        ;

        // Select the access policy depending on user login
        if ($this->getUserModule()->isConnected()) {
            $qb->where(Text::format('(access = "{}" OR access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS], ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        } else {
            $qb->where(Text::format('(access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        }

        return $videoManager->executeQueryBuilder($qb, ['label' => Text::format("%{}%", $category_label)]);


    }

}