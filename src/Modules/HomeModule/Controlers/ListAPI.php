<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/01/2020
 * Time: 17:26
 */

namespace myCLAP\Modules\HomeModule\Controlers;


use myCLAP\ControlerAPI;
use myCLAP\Modules\ManagerModule\ManagerModule;
use myCLAP\Services\RendererExtension;
use Plexus\Utils\Text;

class ListAPI extends ControlerAPI {

    const LIMIT_RECENT_VIDEOS = 10;
    const LIMIT_PLAYLIST_BROADCAST = 20;
    const LIMIT_PLAYLIST_CLASSIC = 100;

    /**
     * @throws \Exception
     */
    public function recent_videos_html() {

        $limit =  intval($this->paramGet('limit', 4));
        $offset = intval($this->paramGet('offset', 0));

        // Set a minimum and a maximum to the limit
        $limit = min(self::LIMIT_RECENT_VIDEOS, max(0, $limit));
        // Set a minimum to the offset
        $offset = max(0, $offset);

        $videos = $this->getVideoList()->recent_videos($limit, $offset);

        $output = [];
        foreach ($videos as $video) {
            $output[] = $this->getRenderer()->render("@HomeModule/listapi/video.html.twig", [
                'video' => $video
            ]);
        }

        $this->success($output);
    }

    /**
     * @throws \Exception
     */
    public function category_videos_html($category_slug) {

        $categoryManager = $this->getModelManager('category');
        $category = $categoryManager->select(['slug' => $category_slug], true);

        if (!$category) {
            $this->success([]);
            return;
        }

        $limit =  intval($this->paramGet('limit', 4));
        $offset = intval($this->paramGet('offset', 0));

        // We only set a minimum for the category videos
        $limit = max(0, $limit);
        // Set a minimum to the offset
        $offset = max(0, $offset);

        $videos = $this->getVideoList()->category_videos($category->label, $limit, $offset);

        $output = [];
        foreach ($videos as $video) {
            $output[] = $this->getRenderer()->render("@HomeModule/listapi/video.html.twig", [
                'video' => $video
            ]);
        }

        $this->success($output);
    }

    /**
     * @throws \Exception
     */
    public function broadcast_playlists_html() {

        $limit =  intval($this->paramGet('limit', 4));
        $offset = intval($this->paramGet('offset', 0));

        // Set a minimum and a maximum to the limit
        $limit = min(self::LIMIT_PLAYLIST_BROADCAST, max(0, $limit));
        // Set a minimum to the offset
        $offset = max(0, $offset);

        $broadcastPlaylists = $this->getPlaylistList()->broadcast($limit, $offset);

        $output = [];
        foreach ($broadcastPlaylists as $playlist) {
            $output[] = $this->getRenderer()->render("@HomeModule/listapi/playlist_broadcast.html.twig", [
                'playlist' => $playlist
            ]);
        }

        $this->success($output);

    }

    /**
     * @throws \Exception
     */
    public function classic_playlists_html() {

        $limit =  intval($this->paramGet('limit', 4));
        $offset = intval($this->paramGet('offset', 0));

        // Set a minimum and a maximum to the limit
        $limit = min(self::LIMIT_PLAYLIST_CLASSIC, max(0, $limit));
        // Set a minimum to the offset
        $offset = max(0, $offset);

        $classicPlaylists = $this->getPlaylistList()->classic($limit, $offset);

        $output = [];
        foreach ($classicPlaylists as $playlist) {

            $videos = $this->getVideoList()->playlist_videos($playlist['slug']);
            $playlist['number_of_videos'] = count($videos);
            $length = min(10, $playlist['number_of_videos']);
            $playlist['videos'] = array_slice($videos, 0, $length);

            $output[] = $this->getRenderer()->render("@HomeModule/listapi/playlist_classic.html.twig", [
                'playlist' => $playlist
            ]);
        }

        $this->success($output);

    }

    /**
     * @throws \Exception
     */
    public function user_favorite_videos_html() {

        if (!$this->getUserModule()->isConnected()) {
            $this->success([]);
            return;
        }

        $limit =  intval($this->paramGet('limit', 4));
        $offset = intval($this->paramGet('offset', 0));

        // We only set a minimum for the user's favorite videos
        $limit = max(0, $limit);
        // Set a minimum to the offset
        $offset = max(0, $offset);

        $videos = $this->getVideoList()->category_videos($this->getUserModule()->getUser()->username, $limit, $offset);

        $output = [];
        foreach ($videos as $video) {
            $output[] = $this->getRenderer()->render("@HomeModule/reaction/video.html.twig", [
                'video' => $video
            ]);
        }

        $this->success($output);

    }
}