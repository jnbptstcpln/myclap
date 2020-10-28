<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 28/12/2019
 * Time: 18:44
 */

namespace myCLAP\Modules\ManagerModule\Controlers;


use myCLAP\Controler;
use myCLAP\Modules\ManagerModule\Forms\PlaylistForm;
use Plexus\Exception\HttpException;
use Plexus\Session;
use Plexus\Utils\Text;

class Playlist extends Controler {

    /**
     * @throws \Exception
     */
    public function middleware() {

        if (!$this->getUserModule()->hasPermission('manager.playlist')) {
            throw HttpException::createFromCode(403);
        }

        $this->getRenderer()->addGlobal('LeftbarActive', 'playlists');
    }

    /**
     * @throws \Exception
     */
    public function index() {

        $playlistManager = $this->getModelManager('playlist');
        $qb = $playlistManager->getQueryBuilder();
        $qb
            ->select('*')
            ->order('modified_on', 'desc')
        ;
        $playlists = $playlistManager->executeQueryBuilder($qb);

        $this->render('@ManagerModule/playlist/index.html.twig', [
            'playlists' => $playlists
        ]);
    }

    /**
     * @throws \Exception
     */
    public function create() {

        $playlistManager = $this->getModelManager('playlist');
        $form = new PlaylistForm();

        // Default value
        $form->created_on->setValue(date('Y-m-d'));
        $form->videos->setValue(json_encode([]));

        if ($this->method('post')) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {
                $playlist = $playlistManager->create();
                $playlist->updateFromForm($form);

                // Generate the slug
                $slug_base = Text::slug($playlist->name);
                $slug = $slug_base;
                $acc = 1;
                while ($playlistManager->select(['slug' => $slug], true)) {
                    $acc += 1;
                    $slug = $slug_base.'-'.$acc;
                }
                $playlist->slug = $slug;

                $playlist->modified_by = $this->getUserModule()->getUser()->username;

                // Check the videos
                $videoManager = $this->getModelManager('video');
                $videos = json_decode($playlist->videos, true);
                if ($videos === null) {
                    $videos = [];
                }
                $_videos = [];
                foreach ($videos as $token) {
                    if ($videoManager->select(['token' => $token], true)) {
                        $_videos[] = $token;
                    }
                }
                $playlist->videos = json_encode($_videos);

                try {
                    $playlistManager->insert($playlist, [
                        'modified_on' => 'NOW()'
                    ]);
                    $this->flash("La playlist a été ajoutée avec succès", 'success');
                    $this->redirect($this->buildRouteUrl('manager-playlist-index'));
                    return;
                } catch (\Exception $e) {
                    $this->log($e, 'playlist');
                    $form->addError("Une erreur a eu lieu lors de la mise à jour de la base de données. La playlist n'a pas été créée.");
                }

            }
        }

        $this->render('@ManagerModule/playlist/create.html.twig', [
            'form' => $form
        ]);
    }

    /**
     * @param $slug
     * @throws HttpException
     * @throws \Exception
     */
    public function edit($slug) {

        $playlistManager = $this->getModelManager('playlist');
        $playlist = $playlistManager->select(['slug' => $slug], true);

        if (!$playlist) {
            throw HttpException::createFromCode(404);
        }

        $form = new PlaylistForm();
        $form->fillWithModel($playlist);

        if ($this->method('post')) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {

                $playlist->updateFromForm($form);


                $playlist->modified_by = $this->getUserModule()->getUser()->username;

                // Check the videos
                $videoManager = $this->getModelManager('video');
                $videos = json_decode($playlist->videos, true);
                if ($videos === null) {
                    $videos = [];
                }
                $_videos = [];
                foreach ($videos as $token) {
                    if ($videoManager->select(['token' => $token], true)) {
                        $_videos[] = $token;
                    }
                }
                $playlist->videos = json_encode($_videos);

                try {
                    $playlistManager->update($playlist, [
                        'modified_on' => 'NOW()'
                    ]);
                    $this->flash("Les changements ont bien été sauvegardés", 'success');
                    $this->refresh();
                    return;
                } catch (\Exception $e) {
                    $this->log($e, 'playlist');
                    $form->addError("Une erreur a eu lieu lors de la mise à jour de la base de données. Les changements n'ont pas été sauvegardés.");
                }

            }
        }

        $this->render('@ManagerModule/playlist/edit.html.twig', [
            'playlist' => $playlist,
            'form' => $form
        ]);
    }

    public function delete() {

    }
}