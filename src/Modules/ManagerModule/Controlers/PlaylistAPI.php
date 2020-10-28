<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 28/12/2019
 * Time: 18:44
 */

namespace myCLAP\Modules\ManagerModule\Controlers;


use myCLAP\ControlerAPI;
use myCLAP\Modules\ManagerModule\Forms\PlaylistForm;
use myCLAP\Modules\SearchModule\SearchModule;
use Plexus\Exception\HttpException;

class PlaylistAPI extends ControlerAPI {

    /**
     * @throws \Exception
     */
    public function middleware() {

        if (!$this->getUserModule()->hasPermission('manager.playlist')) {
            throw HttpException::createFromCode(403);
        }
    }

    /**
     * @throws \Exception
     */
    public function index() {
        $this->render('@ManagerModule/playlist/index.html.twig');
    }

    /**
     * @throws \Plexus\Exception\ModelException
     * @throws \Plexus\Exception\RenderException
     */
    public function search_videos_html() {
        $value = $this->paramGet('value');

        if (strlen($value) == 0) {
            $this->success([]);
            return;
        }

        $videos = $this->getSearchModule()->perform_search_with_token($value, 10, 0);

        $output = [];
        foreach ($videos as $video) {
            $output[] = $this->getRenderer()->render("@ManagerModule/playlistapi/search_video.html.twig", [
                'video' => $video
            ]);
        }

        $this->success($output);
    }

    /**
     * @throws \Exception
     */
    public function videos_html() {

        $tokens = json_decode($this->paramGet('tokens'), true);

        if ($tokens === null) {
            $this->error(400, "Le format de la demande est incorrect.");
            return;
        }

        $videoManager = $this->getModelManager('video');
        $output = [];
        foreach ($tokens as $token) {
            $video = $videoManager->select(['token' => $token], true);
            if ($video) {
                $output[] = $this->getRenderer()->render("@ManagerModule/playlistapi/video.html.twig", [
                    'video' => $video
                ]);
            }
        }

        $this->success($output);
    }

    /**
     * @return \Plexus\Module|SearchModule
     * @throws \Exception
     */
    public function getSearchModule() {
        return $this->getContainer()->getModule('SearchModule');
    }
}