<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 27/12/2019
 * Time: 17:06
 */

namespace myCLAP\Modules\WatchModule\Controlers;


use myCLAP\Controler;
use myCLAP\Modules\ManagerModule\ManagerModule;
use Plexus\Exception\HttpException;
use Plexus\Session;

class Index extends Controler {


    /**
     * @param $video_token
     * @throws HttpException
     * @throws \Exception
     */
    public function index($video_token) {

        Session::pushCurrentURL();

        $videoManager = $this->getModelManager('video');
        $video = $videoManager->select(['token' => $video_token], true);

        if (!$video) {
            $this->getResponse()->setStatusCode(404);
            $this->render('@WatchModule/index/notfound.html.twig');
            return;
        }

        if ($video->upload_status !== ManagerModule::UPLOAD_STATUS["UPLOAD_END"]) {
            throw HttpException::createFromCode(404);
        }

        // Dealing with the video's access policy
        switch ($video->access) {
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PRIVATE]:
                if (!$this->getUserModule()->hasPermission('myclap.private')) {
                    $this->render('@WatchModule/index/private.html.twig', [
                        'video' => $video
                    ]);
                    return;
                }
                break;
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS]:
                if (!$this->getUserModule()->isConnected()) {
                    $this->render('@WatchModule/index/login.html.twig', [
                        'video' => $video
                    ]);
                    return;
                }
                break;
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_UNLINKED]:
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]:
            default:
                break;
        }

        if ($this->getUserModule()->isConnected()) {
            $reactionManager = $this->getModelManager('video_reaction');
            if ($reactionManager->select(['video_token' => $video->token, 'username' => $this->getUserModule()->getUser()->username], true)) {
                $video->user_did_like = true;
            }
        }


        $this->render('@WatchModule/index/index.html.twig', [
            'video' => $video
        ]);
    }

    /**
     * @param $video_token
     * @throws \Exception
     */
    public function download($video_token) {
        $this->redirect($this->buildRouteUrl("watch-media-video-download", $video_token));
    }

    /**
     * @param $video_token
     * @throws HttpException
     * @throws \Exception
     */
    public function playlist($playlist_slug, $video_token) {

        $this->getRenderer()->addGlobal('LeftbarActive', 'playlists');

        $playlistManager = $this->getModelManager('playlist');
        $playlist = $playlistManager->select(['slug' => $playlist_slug], true);

        if (!$playlist) {
            throw HttpException::createFromCode(404);
        }

        $videos = json_decode($playlist->videos, true);
        $videos = ($videos === null) ? [] : $videos;

        if (!in_array($video_token, $videos)) {
            throw HttpException::createFromCode(404);
        }

        $videoIndex = 0;

        $videos = $this->getVideoList()->playlist_videos($playlist->slug);
        foreach ($videos as $i => &$_video) {
            if ($video_token == $_video['token']) {
                $videoIndex = $i;
            }
            $_video['index'] = $i+1;
        }

        $playlist->_videos = $videos;

        $videoManager = $this->getModelManager('video');
        $video = $videoManager->select(['token' => $video_token], true);

        if (!$video) {
            throw HttpException::createFromCode(404);
        }

        $video->index = $videoIndex+1;

        if ($video->upload_status !== ManagerModule::UPLOAD_STATUS["UPLOAD_END"]) {
            throw HttpException::createFromCode(404);
        }

        // Dealing with the video's access policy
        switch ($video->access) {
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PRIVATE]:
                if (!$this->getUserModule()->hasPermission('myclap.private')) {
                    $this->render('@WatchModule/index/private.html.twig', [
                        'video' => $video
                    ]);
                    return;
                }
                break;
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS]:
                if (!$this->getUserModule()->isConnected()) {
                    $this->render('@WatchModule/index/login.html.twig', [
                        'video' => $video
                    ]);
                    return;
                }
                break;
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_UNLINKED]:
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]:
            default:
                break;
        }

        if ($this->getUserModule()->isConnected()) {
            $reactionManager = $this->getModelManager('video_reaction');
            if ($reactionManager->select(['video_token' => $video->token, 'username' => $this->getUserModule()->getUser()->username], true)) {
                $video->user_did_like = true;
            }
        }


        $this->render('@WatchModule/index/playlist.html.twig', [
            'playlist' => $playlist,
            'video' => $video
        ]);
    }
}