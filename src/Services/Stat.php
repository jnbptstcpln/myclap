<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 31/12/2019
 * Time: 16:12
 */

namespace myCLAP\Services;


use myCLAP\Modules\ManagerModule\ManagerModule;
use myCLAP\Service;
use Plexus\Utils\Text;

class Stat extends Service {

    /**
     * @param $video_token
     * @return int
     * @throws \Plexus\Exception\ModelException
     */
    public function views_for_video($video_token) {
        $viewManager = $this->getContainer()->getModelManager('video_view');
        $qb = $viewManager->getQueryBuilder();
        $qb
            ->select('COUNT(*)', 'views')
            ->where('video_token = :video_token')
            ->where('count_as_view = 1')
        ;
        $views = $viewManager->executeQueryBuilder($qb, ['video_token' => $video_token]);
        return intval($views[0]['views']);
    }

    /**
     * @param $category_label
     * @return int
     * @throws \Exception
     */
    public function number_of_videos_for_category($category_label) {
        $videoManager = $this->getContainer()->getModelManager('video');
        $qb = $videoManager->getQueryBuilder();
        $qb
            ->select('COUNT(*)', 'number_of_videos')
            ->where('upload_status = 0')
            ->where('categories LIKE :label')
        ;

        // Select the access policy depending on user login
        if ($this->getUserModule()->isConnected()) {
            $qb->where(Text::format('(access = "{}" OR access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS], ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        } else {
            $qb->where(Text::format('(access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        }

        $videos = $videoManager->executeQueryBuilder($qb, ['label' => Text::format("%{}%", $category_label)]);
        return intval($videos[0]['number_of_videos']);
    }

    /**
     * @param $username
     * @return int
     * @throws \Exception
     */
    public function number_of_favorite_videos_for_user($username) {
        $videoManager = $this->getContainer()->getModelManager('video_reaction');
        $qb = $videoManager->getQueryBuilder();
        $qb
            ->select('COUNT(*)', 'number_of_videos')
            ->join('video', 'video.token = video_reaction.video_token')
            ->where('video.upload_status = 0')
            ->where('video_reaction.username = :username')
            ->order('video_reaction.created_on', 'desc')
        ;

        // Select the access policy depending on user login
        if ($this->getUserModule()->isConnected()) {
            $qb->where(Text::format('(video.access = "{}" OR video.access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS], ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        } else {
            $qb->where(Text::format('(video.access = "{}")', ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]));
        }

        $this->getContainer()->getApplication()->log($qb->query());

        $videos = $videoManager->executeQueryBuilder($qb, ['username' => $username]);
        return intval($videos[0]['number_of_videos']);
    }



}