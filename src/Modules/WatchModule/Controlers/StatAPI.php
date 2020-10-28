<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 27/12/2019
 * Time: 17:06
 */

namespace myCLAP\Modules\WatchModule\Controlers;


use myCLAP\Controler;
use myCLAP\ControlerAPI;
use myCLAP\Modules\ManagerModule\ManagerModule;
use Plexus\Exception\HttpException;
use Plexus\Utils\Randomizer;

class StatAPI extends ControlerAPI {


    /**
     * @param $video_token
     * @throws HttpException
     * @throws \Exception
     */
    public function init($video_token) {

        $videoManager = $this->getModelManager('video');
        $video = $videoManager->select(['token' => $video_token], true);

        if (!$video) {
            throw HttpException::createFromCode(404);
        }

        if ($video->upload_status !== ManagerModule::UPLOAD_STATUS["UPLOAD_END"]) {
            throw HttpException::createFromCode(404);
        }

        // Dealing with the video's access policy
        switch ($video->access) {
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PRIVATE]:
                if (!$this->getUserModule()->hasPermission('myclap.private')) {
                    throw HttpException::createFromCode(403);
                }
                break;
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS]:
                if (!$this->getUserModule()->isConnected()) {
                    throw HttpException::createFromCode(401);
                }
                break;
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_UNLINKED]:
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]:
            default:
                break;
        }

        $sid = session_id();

        $viewManager = $this->getModelManager('video_view');
        $views = $viewManager->select(['video_token' => $video->token, 'php_sid' => $sid]);
        // If playback session were initiated more than 20 times during that session we stop counting
        if ($views->length() >= 20) {
            $this->success(['playback_sid' => null]);
            return;
        }


        $viewManager = $this->getModelManager('video_view');
        $views = $viewManager->select(['video_token' => $video->token, 'php_sid' => $sid, 'count_as_view' => 1]);

        // If a view was count during the php session we stop counting
        if ($views->length() >= 1) {
            $this->success(['playback_sid' => null]);
            return;
        }

        $view = $viewManager->create();
        $view->video_token = $video->token;
        $view->php_sid = $sid;
        $view->playback_sid = Randomizer::generate_unique_token(30, function($value) use ($viewManager) {
            return $viewManager->select(['playback_sid' => $value], true) == null;
        });
        // Save the username if the user is connected
        if ($this->getUserModule()->isConnected()) {
            $view->username = $this->getUserModule()->getUser()->username;
        }

        try {
            $viewManager->insert($view, [
                'created_on' => 'NOW()',
                'updated_on' => 'NOW()',
            ]);
        } catch (\Exception $e) {
            $this->log($e, 'stat');
            $this->error(500, "");
            return;
        }

        $this->success(['playback_sid' => $view->playback_sid]);
    }

    /**
     * @param $video_token
     * @throws HttpException
     * @throws \Exception
     */
    public function update($video_token) {
        $videoManager = $this->getModelManager('video');
        $video = $videoManager->select(['token' => $video_token], true);

        if (!$video) {
            throw HttpException::createFromCode(404);
        }

        if ($video->upload_status !== ManagerModule::UPLOAD_STATUS["UPLOAD_END"]) {
            throw HttpException::createFromCode(404);
        }

        // Dealing with the video's access policy
        switch ($video->access) {
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PRIVATE]:
                if (!$this->getUserModule()->hasPermission('myclap.private')) {
                    throw HttpException::createFromCode(403);
                }
                break;
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS]:
                if (!$this->getUserModule()->isConnected()) {
                    throw HttpException::createFromCode(401);
                }
                break;
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_UNLINKED]:
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]:
            default:
                break;
        }

        $sid = session_id();
        $playback_sid = $this->paramPost('playback_sid');

        $viewManager = $this->getModelManager('video_view');
        $view = $viewManager->select(['video_token' => $video->token, 'php_sid' => $sid, 'playback_sid' => $playback_sid], true);

        if (!$view) {
            $this->error(500, "");
            return;
        }

        // Calculate if the view is valid
        $this->log(time() - strtotime($view->created_on), 'time');
        if (time() - strtotime($view->created_on) > 30 && !$view->count_as_view) {
            $view->count_as_view = true;
            try {
                $video->views += 1;
                $videoManager->update($video);
            } catch (\Exception $e) {
                $this->log($e, 'stat');
            }
        }

        try {
            $viewManager->update($view, [
                'updated_on' => 'NOW()'
            ]);
        } catch (\Exception $e) {
            $this->log($e, 'stat');
            $this->error(500, "");
            return;
        }

        $this->success(['playback_sid' => $view->playback_sid]);
    }

}