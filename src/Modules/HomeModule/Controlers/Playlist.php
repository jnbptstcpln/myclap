<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 25/12/2019
 * Time: 11:19
 */

namespace myCLAP\Modules\HomeModule\Controlers;


use myCLAP\Controler;
use myCLAP\Modules\ManagerModule\ManagerModule;
use Plexus\Exception\HttpException;
use Plexus\Session;

class Playlist extends Controler {

    /**
     * @throws \Exception
     */
    public function middleware() {
        $this->getRenderer()->addGlobal('LeftbarActive', 'playlists');
    }

    /**
     * @throws \Exception
     */
    public function index() {

        Session::pushCurrentURL();

        $broadcastPlaylists = $this->getPlaylistList()->broadcast(4, 0);
        $classicPlaylists = $this->getPlaylistList()->classic(8, 0);

        foreach ($classicPlaylists as &$playlist) {
            $videos = $this->getVideoList()->playlist_videos($playlist['slug']);
            $playlist['number_of_videos'] = count($videos);
            $length = min(10, $playlist['number_of_videos']);
            $playlist['videos'] = array_slice($videos, 0, $length);
        }

        $this->render('@HomeModule/playlist/index.html.twig', [
            'broadcastPlaylists' => $broadcastPlaylists,
            'classicPlaylists' => $classicPlaylists
        ]);
    }

    /**
     * @param $slug
     * @throws \Exception
     */
    public function details($slug) {

        Session::pushCurrentURL();

        $playlistManager = $this->getModelManager('playlist');
        $playlist = $playlistManager->select(['slug' => $slug], true);

        if (!$playlist) {
            throw HttpException::createFromCode(404);
        }

        // Dealing with the playlist's access policy
        switch ($playlist->access) {
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PRIVATE]:
                if (!$this->getUserModule()->hasPermission('myclap.private')) {
                    throw HttpException::createFromCode(403);
                }
                break;
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS]:
                if (!$this->getUserModule()->isConnected()) {
                    $this->redirect($this->buildRouteUrl('user-login'));
                    return;
                }
                break;
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_UNLINKED]:
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]:
            default:
                break;
        }

        $videos = $this->getVideoList()->playlist_videos($playlist->slug);
        foreach ($videos as $i => &$video) {
            $video['index'] = $i+1;
        }
        $playlist->_videos = $videos;

        $this->render('@HomeModule/playlist/details.html.twig', [
            'playlist' => $playlist
        ]);
    }

}