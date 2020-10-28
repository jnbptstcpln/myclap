<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 28/12/2019
 * Time: 18:44
 */

namespace myCLAP\Modules\ManagerModule\Controlers;


use myCLAP\Controler;
use myCLAP\Modules\ManagerModule\Forms\VideoForm;
use myCLAP\Modules\ManagerModule\ManagerModule;
use myCLAP\Services\ImageLib;
use Plexus\Exception\HttpException;
use Plexus\Form;
use Plexus\FormField\CSRFInput;
use Plexus\FormField\FileInput;
use Plexus\Model;
use Plexus\Session;
use Plexus\Utils\Randomizer;
use Plexus\Utils\Text;

class Video extends Controler {

    /**
     * @throws \Exception
     */
    public function middleware() {

        if (
            !$this->getUserModule()->hasPermissionGroup('manager.video')
        ) {
            throw HttpException::createFromCode(403);
        }

        $this->getRenderer()->addGlobal('LeftbarActive', 'videos');
    }

    /**
     * @throws \Exception
     */
    public function index() {

        $videoManager = $this->getModelManager('video');
        $qb = $videoManager->getQueryBuilder();
        $qb
            ->select('*')
            ->order('uploaded_on', 'desc')
        ;
        $videos = $videoManager->executeQueryBuilder($qb);

        $this->render('@ManagerModule/video/index.html.twig', [
            'videos' => $videos
        ]);
    }

    /**
     * @throws HttpException
     * @throws \Exception
     */
    public function create() {

        if (!$this->getUserModule()->hasPermission('manager.video.upload')) {
            throw HttpException::createFromCode(403);
        }

        $videoManager = $this->getModelManager('video');
        $form = new VideoForm($this->getContainer());

        // Default value
        $form->created_on->setValue(date('Y-m-d'));
        $form->categories->setValue(json_encode([]));

        if ($this->method('post')) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {
                // Create the video entity
                $video = $videoManager->create();
                // Update from the form
                $video->updateFromForm($form);

                // Deal with non-form data
                $video->uploaded_by = $this->getUserModule()->getUser()->username;
                $video->token = Randomizer::generate_unique_token(6, function($value) use ($videoManager) {
                    return $videoManager->select(['token' => $value], true) == null;
                });
                $video->upload_status = ManagerModule::UPLOAD_STATUS["UPLOAD_NULL"];

                // Deal with the thumbnail
                if ($form->thumbnail_file->isFileValid()) {
                    $thumbnail_file = $form->thumbnail_file->getFile();
                    $imageImage = $this->getImageLib()->type($thumbnail_file['tmp_name'], false);
                    if ($imageImage != IMAGETYPE_JPEG && $imageImage != IMAGETYPE_PNG) {
                        $form->addError("Votre miniature doit être un fichier PNG ou JPEG.");
                    } else {
                        try {
                            $video->thumbnail_identifier = $this->getFileManager()->move_upload_file($thumbnail_file['tmp_name'], 'video_thumbnail:1080');
                        } catch (\Exception $e) {
                            $this->log($e);
                            $form->addError("Impossible d'enregistrer la miniature pour le moment, veuillez réessayer plus tard.");
                        }
                    }
                }

                if ($form->validate()) {
                    try {
                        $videoManager->insert($video, [
                            'uploaded_on' => 'NOW()'
                        ]);
                        $this->redirect($this->buildRouteUrl('manager-video-upload', $video->token));
                        return;
                    } catch (\Exception $e) {
                        $this->log($e, 'video');
                        $form->addError("Une erreur a eu lieu lors de la mise à jour de la base de données. La vidéo n'a pas été créée.");
                    }
                }

            }
        }

        $this->render('@ManagerModule/video/create.html.twig', [
            'form' => $form
        ]);

    }

    /**
     * @param $token
     * @throws HttpException
     * @throws \Exception
     */
    public function edit($token) {

        $videoManager = $this->getModelManager('video');
        $video = $videoManager->select(['token' => $token], true);

        if (!$video) {
            throw HttpException::createFromCode(404);
        }

        $form = new VideoForm($this->getContainer());
        $form->fillWithModel($video);

        if ($this->method('post')) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {
                $video->updateFromForm($form);

                // Deal with the thumbnail
                if ($form->thumbnail_file->isFileValid()) {
                    $thumbnail_file = $form->thumbnail_file->getFile();
                    $imageImage = $this->getImageLib()->type($thumbnail_file['tmp_name'], false);
                    if ($imageImage != IMAGETYPE_JPEG && $imageImage != IMAGETYPE_PNG) {
                        $form->addError("Votre miniature doit être un fichier PNG ou JPEG.");
                    } else {
                        try {
                            $video->thumbnail_identifier = $this->getFileManager()->move_upload_file($thumbnail_file['tmp_name'], 'video_thumbnail:1080');

                            // Delete previously generated thumbnail
                            $thumbnailManager = $this->getModelManager('video_thumbnail');
                            $thumbnails = $thumbnailManager->select(['video_token' => $video->token]);
                            $thumbnails->each(function(Model $thumbnail) {
                                $fileManager = $this->getFileManager();
                                if ($fileManager->file_exists($thumbnail->file_identifier)) {
                                    unlink($fileManager->get($thumbnail->file_identifier));
                                }
                                $thumbnail->getManager()->delete($thumbnail);
                            });

                        } catch (\Exception $e) {
                            $this->log($e);
                            $form->addError("Impossible d'enregistrer la nouvelle miniature pour le moment, veuillez réessayer plus tard.");
                        }
                    }
                }

                if ($form->validate()) {
                    try {
                        $videoManager->update($video);
                        $this->flash("Les changements ont bien sauvegardés !", 'success');
                        $this->refresh();
                        return;
                    } catch (\Exception $e) {
                        $this->log($e, 'video');
                        $form->addError("Une erreur a eu lieu lors de la mise à jour de la base de données. La vidéo n'a pas été modifiée.");
                    }
                }

            }
        }

        $this->render('@ManagerModule/video/edit.html.twig', [
            'video' => $video,
            'form' => $form
        ]);

    }

    /**
     * @param $token
     * @throws HttpException
     * @throws \Exception
     */
    public function upload($token) {

        $videoManager = $this->getModelManager('video');
        $video = $videoManager->select(['token' => $token], true);

        if (!$video) {
            throw HttpException::createFromCode(404);
        }

        if ($video->upload_status === ManagerModule::UPLOAD_STATUS["UPLOAD_END"]) {
            throw HttpException::createFromCode(404);
        }

        $upload = null;
        if ($video->upload_status === ManagerModule::UPLOAD_STATUS["UPLOAD_INIT"]) {
            $uploadManager = $this->getModelManager('video_upload');
            $upload = $uploadManager->select(['video_token' => $video->token], true);
        }

        $form_upload = new Form('post', '');
        $form_upload
            ->addField(new FileInput('video', [
                'label' => "Votre vidéo à mettre en ligne",
                'classes' => ['fulgur-fileinput'],
                'help_text' => "Fichier MP4 de préférence encodé et optimisé pour le web",
            ]))
        ;

        $this->render('@ManagerModule/video/upload.html.twig', [
            'video' => $video,
            'upload' => $upload,
            'form' => $form_upload
        ]);

    }

    /**
     * @param $token
     * @throws HttpException
     * @throws \Exception
     */
    public function delete($token) {

        $videoManager = $this->getModelManager('video');
        $video = $videoManager->select(['token' => $token], true);

        if (!$video) {
            throw HttpException::createFromCode(404);
        }

        $form = new Form("post");
        $form->addField(new CSRFInput("video-delete"));

        if ($this->method("post")) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {
                try {
                    // Delete the video from the database
                    $videoManager->delete($video);

                    // Delete the thumbnails
                    $thumbnailManager = $this->getModelManager('video_thumbnail');
                    $thumbnails = $thumbnailManager->select(['video_token' => $video->token]);
                    try {
                        $thumbnails->each(function(Model $thumbnail) {
                            $fileManager = $this->getFileManager();
                            if ($fileManager->file_exists($thumbnail->file_identifier)) {
                                if (!unlink($fileManager->get($thumbnail->file_identifier))) {
                                    $this->log(Text::format("Impossible de supprimer la miniature '{}'", $thumbnail->file_identifier));
                                }
                            }
                            $thumbnail->getManager()->delete($thumbnail);
                        });
                    } catch (\Exception $e) {
                        $this->log($e);
                    }

                    try {
                        $video_identifier = $video->file_identifier;
                        if (strlen($video_identifier) > 0) {
                            $fileManager = $this->getFileManager();
                            if ($fileManager->file_exists($video_identifier)) {
                                if (!unlink($fileManager->get($video_identifier))) {
                                    $this->log(Text::format("Impossible de supprimer le fichier video '{}'", $video_identifier));
                                };
                            }
                        }
                    } catch (\Exception $e) {
                        $this->log($e);
                    }

                    $this->flash("La vidéo a bien été supprimée", 'info');
                    $this->redirect($this->buildRouteUrl("manager-video-index"));
                    return;
                } catch (\Exception $e) {
                    $this->log($e);
                    $form->addError("Une erreur a eu lieu lors de la modification de la base de données. La vidéo n'a pas été supprimée.");
                }

            }
        }

        $this->render('@ManagerModule/video/delete.html.twig', [
            'video' => $video,
            'form' => $form
        ]);

    }

}