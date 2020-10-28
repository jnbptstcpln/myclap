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
use Plexus\Utils\Text;

class Media extends Controler {

    /**
     * @param $token
     * @throws \Plexus\Exception\BundleException
     * @throws \Plexus\Exception\FileException
     * @throws \Exception
     */
    public function video($video_token) {

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

        $fileManager = $this->getFileManager();

        if (!$fileManager->file_exists($video->file_identifier)) {
            throw HttpException::createFromCode(500);
        }

        $reponse = $this->getResponse();
        $reponse->xsendfile($fileManager->get($video->file_identifier), Text::slug($video->name));

    }

    /**
     * @param $token
     * @throws \Plexus\Exception\BundleException
     * @throws \Plexus\Exception\FileException
     * @throws \Exception
     */
    public function video_download($video_token) {

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

        $fileManager = $this->getFileManager();

        if (!$fileManager->file_exists($video->file_identifier)) {
            throw HttpException::createFromCode(500);
        }

        $reponse = $this->getResponse();
        $reponse->xsendfile_download(
            $fileManager->get($video->file_identifier),
            Text::format("{}.mp4", Text::slug($video->name)),
            "application/octet-stream"
        );

    }

    const SIZES = [
        1080 => [1920, 1080],
        720 => [1280, 720],
        480 => [720, 480],
        360 => [480, 360],
        240 => [352, 240],
        144 => [256, 144],
        72 => [128, 72],
        18 => [32, 18]
    ];

    /**
     * @param $video_token
     * @param $height
     * @throws HttpException
     * @throws \Exception
     */
    public function thumbnail($video_token, $height) {

        $videoManager = $this->getModelManager('video');
        $video = $videoManager->select(['token' => $video_token], true);

        if (!$video) {
            throw HttpException::createFromCode(404);
        }

        // Dealing with the video's access policy
        switch ($video->access) {
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PRIVATE]:
                if (!$this->getUserModule()->hasPermission('myclap.private')) {
                    $this->redirect('/static/myclap/thumbnail/placeholder.png');
                    return;
                }
                break;
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_CENTRALIENS]:
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_UNLINKED]:
            case ManagerModule::CONTENT_ACCESS[ManagerModule::CONTENT_ACCESS_PUBLIC]:
            default:
                break;
        }

        if (strlen($video->thumbnail_identifier) === 0) {
            $this->redirect('/static/myclap/thumbnail/placeholder.png');
            return;
        }

        if (!$this->getFileManager()->file_exists($video->thumbnail_identifier)) {
            $this->redirect('/static/myclap/thumbnail/placeholder.png');
            return;
        }


        $height = intval($height);
        $size = null;
        if (!isset($this::SIZES[$height])) {
            $size = $this::SIZES[1080];
        } else {
            $size = $this::SIZES[$height];
        }

        $thumbnailManager = $this->getModelManager('video_thumbnail');
        $thumbnail = $thumbnailManager->select(['video_token' => $video->token, 'height' => $size[1]], true);

        $response = $this->getResponse();

        if (!$thumbnail) {
            try {
                // Generate the thumbnail
                $identifier = $this->getImageLib()->resize($video->thumbnail_identifier, Text::format('video_thumbnail:{}', $size[1]), $size[0], $size[1]);

                var_dump($identifier);

                $thumbnail = $thumbnailManager->create();
                $thumbnail->video_token = $video->token;
                $thumbnail->file_identifier = $identifier;
                $thumbnail->height = $size[1];
                $thumbnailManager->insert($thumbnail);
            } catch (\Exception $e) {
                $this->log($e, 'thumbnail_resize');
                $response->xsendfile($this->getFileManager()->get($video->thumbnail_identifier));
                return;
            }
        }


        $filepath = $this->getFileManager()->get($thumbnail->file_identifier);
        $filename = Text::slug($video->name);
        $mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filepath);

        $response->setStatusCode(200);
        $response->header('X-Sendfile', $filepath);
        $response->header('Content-type', $mimetype);
        $response->header('Content-Disposition', ('inline') . '; filename="'.$filename.'"');
        // In order to let the browser put the thumbnail in its cache
        header("Cache-Control: private,max-age=864000");
    }
}