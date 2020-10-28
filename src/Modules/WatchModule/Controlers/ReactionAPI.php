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

class ReactionAPI extends ControlerAPI {


    /**
     * @throws \Exception
     */
    public function middleware() {

        if (!$this->getUserModule()->isConnected()) {
            $this->error(401, "Vous devez être connecté pour effectuer cette action");
            return;
        }

    }

    /**
     * @param $video_token
     * @throws HttpException
     * @throws \Exception
     */
    public function toggle($video_token) {

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

        $reactionsManager = $this->getModelManager('video_reaction');
        $reaction = $reactionsManager->select(['video_token' => $video->token, 'username' => $this->getUserModule()->getUser()->username], true);

        $active = null;

        if ($reaction) {
            try {
                $video->reactions = max(0, $video->reactions-1);
                $videoManager->update($video);
                $reactionsManager->delete($reaction);
                $active = false;
            } catch (\Exception $e) {
                $this->log($e, 'reaction');
                $this->error(500, "Une erreur est survenue lors de l'enregsitrement de votre action...");
                return;
            }
        } else {
            try {
                $video->reactions = $video->reactions+1;
                $videoManager->update($video);
                $reaction = $reactionsManager->create();
                $reaction->video_token = $video->token;
                $reaction->username = $this->getUserModule()->getUser()->username;
                $reactionsManager->insert($reaction, [
                    'created_on' => 'NOW()'
                ]);
                $active = true;
            } catch (\Exception $e) {
                $this->log($e, 'reaction');
                $this->error(500, "Une erreur est survenue lors de l'enregsitrement de votre action...");
                return;
            }
        }

        $this->success([
            'active' => $active
        ]);
    }
}