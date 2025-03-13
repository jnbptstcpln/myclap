<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 29/12/2019
 * Time: 21:06
 */

namespace myCLAP\Modules\ManagerModule\Controlers;


use myCLAP\ControlerAPI;
use myCLAP\Modules\ManagerModule\ManagerModule;
use Plexus\Exception\HttpException;
use Plexus\Form;
use Plexus\FormField\FileInput;
use Plexus\FormField\NumberInput;
use Plexus\Session;
use Plexus\Utils\Logger;
use Plexus\Validator\CustomValidator;
use Plexus\Validator\FileSizeValidator;
use Plexus\Validator\RangeValidator;

class VideoUploadAPI extends ControlerAPI {

    const MIN_CHUNK_SIZE = 0.1; // Megabytes
    const MAX_CHUNK_SIZE = 10; // Megabytes

    /**
     * @throws \Exception
     */
    public function middleware() {

        if (!$this->getUserModule()->hasPermission('manager.video.upload')) {
            throw HttpException::createFromCode(403);
        }
    }

    /**
     * @param $video_token
     * @throws \Exception
     */
    public function init($video_token) {
        $videoManager = $this->getModelManager('video');
        $video = $videoManager->select(['token' => $video_token], true);

        if (!$video) {
            $this->error(404, "La ressource n'existe pas.");
            return;
        }

        // Check the video wasn't already uploaded
        if ($video->upload_status === ManagerModule::UPLOAD_STATUS["UPLOAD_END"]) {
            $this->error(400, "La ressource a déjà été téléversée.");
            return;
        }

        $fileManager = $this->getFileManager();

        // Get the info send by the front-end
        $file_name = $this->paramPost('fileName');
        $file_size = $this->paramPost('fileSize');

        // Check if any upload was created
        $uploadManager = $this->getModelManager('video_upload');
        $upload = $uploadManager->select(['video_token' => $video->token], true);

        // Check if an upload was already initiated
        if ($upload) {
            // We're going to resume the upload
            $tempFileIdentifier = $upload->file_identifier;
            $startIndex = 0;

            if ($file_size != $upload->file_size) {
                $this->error(400, "Votre fichier doit être le même que celui que vous aviez commencé à envoyer.");
                return;
            }

            $this->log($fileManager->file_exists($tempFileIdentifier));

            // Check if the temp file was created
            if ($fileManager->file_exists($tempFileIdentifier)) {
                $startIndex = filesize($fileManager->get($tempFileIdentifier));
            } else {
                try {
                    $upload->file_identifier = $fileManager->create_file('video_upload');
                    $uploadManager->update($upload, [
                        'created_on' => 'NOW()'
                    ]);
                } catch (\Exception $e) {
                    $this->log($e, 'video_upload');
                    $this->error(500, "Impossible de charger la ressource pour le moment.");
                    return;
                }
            }

            $this->success([
                'startIndex' => $startIndex,
                'chunkSize' => intval(self::MIN_CHUNK_SIZE*10**6)
            ]);
            return;

        } else {
            // We're going to create the upload video
            $upload = $uploadManager->create();
            $upload->video_token = $video->token;
            $upload->file_name = $file_name;
            $upload->file_size = $file_size;
            $upload->file_identifier = "";
            $upload->created_by = $this->getUserModule()->getUser()->username;

            try {
                $upload->file_identifier = $fileManager->create_file('video_upload');
                $uploadManager->insert($upload, [
                    'created_on' => 'NOW()'
                ]);

                // Update the video upload_status
                $video->upload_status = ManagerModule::UPLOAD_STATUS["UPLOAD_INIT"];
                $videoManager->update($video);

            } catch (\Exception $e) {
                $this->log($e, 'video_upload');
                $this->error(500, "Impossible de charger la vidéo pour le moment.");
                return;
            }

            $this->success([
                'startIndex' => 0,
                'chunkSize' => intval(self::MIN_CHUNK_SIZE*10**6)
            ]);
            return;
        }
    }

    /**
     * @param $video_token
     * @throws \Exception
     */
    public function process($video_token) {

        $videoManager = $this->getModelManager('video');
        $video = $videoManager->select(['token' => $video_token], true);

        if (!$video) {
            $this->error(404, "La ressource n'existe pas.");
            return;
        }

        // Check the video wasn't already uploaded
        if ($video->upload_status === ManagerModule::UPLOAD_STATUS["UPLOAD_END"]) {
            $this->error(400, "La ressource a déjà été téléversée.");
            return;
        }

        // Check if any upload was created
        $uploadManager = $this->getModelManager('video_upload');
        $upload = $uploadManager->select(['video_token' => $video->token], true);

        if (!$upload) {
            $this->error(400, "Le téléversement n'a pas été configuré.");
            return;
        }

        $fileManager = $this->getFileManager();
        $tempFileIdentifier = $upload->file_identifier;
        $startIndex = 0;

        if (!$fileManager->file_exists($tempFileIdentifier)) {
            $this->error(400, "Le téléversement n'a pas été initialisé correctement.");
            return;
        }
        $outputFilepath = $fileManager->get($tempFileIdentifier);
        $startIndex = filesize($outputFilepath);

        // Get the data send by the user
        $file_form = new Form('post', '', [
            new NumberInput('startIndex', [
                'validators' => [
                    new CustomValidator(function ($value) use ($startIndex) {
                        return intval($value) == intval($startIndex);
                    }, "Le découpage du fichier à envoyer est erroné.")
                ]
            ]),
            new NumberInput('startedOn', [
                'validators' => [
                    new RangeValidator(0, time()+10)
                ]
            ]),
            new NumberInput('chunkSize', [
                'validators' => [
                    new RangeValidator(intval(self::MIN_CHUNK_SIZE*10**6)-1, intval(self::MAX_CHUNK_SIZE*10**6)+1)
                ]
            ]),
            new FileInput('fileChunk', [
                'validators' => [
                    new FileSizeValidator(intval(self::MAX_CHUNK_SIZE*10**6)+1)
                ]
            ])
        ]);
        $file_form->fillWithArray($this->paramsPost());

        if (!$file_form->validate()) {


            $errors = $file_form->getErrors(true)->toArray();
            $errors_text = [];
            foreach ($errors as $i => $error) {
                $errors_text[] = $error->getMessage();
            }

            $this->log($_REQUEST, 'video_upload');
            $this->log(join("\n", $errors_text));

            $this->error(400, join("\n", $errors_text));
            return;
        }

        if ($file_form->fileChunk->isFileValid()) {
            $fileChunk = $file_form->fileChunk->getFile();

            // Open a binary reading stream from the input file
            $inputHandler = fopen($fileChunk['tmp_name'], 'rb');

            // Check if the stream was successfully opened
            if (!$inputHandler) {
                $this->log("Erreur lors de l'ouverture de la ressource vers le fichier d'entrée.");
                $this->error(500, "Impossible de charger la ressource pour le moment.");
                return;
            }

            // Get the content of the fileChunk
            $chunkContent = fread($inputHandler, filesize($fileChunk['tmp_name']));
            fclose($inputHandler);

            // Open a binary writing stream to the temp file
            $outputHandler = fopen($outputFilepath, 'ab');
            // Check if the stream was successfully opened
            if (!$outputHandler) {
                $this->log("Erreur lors de l'ouverture de la ressource vers le fichier de sortie.");
                $this->error(500, "Impossible de charger la ressource pour le moment.");
                return;
            }

            // Append the chunkContent to the output file
            if (fwrite($outputHandler, $chunkContent) === false) {
                $this->log("Erreur lors de l'écriture du chunk vers le fichier de sortie.");
                $this->error(500, "Impossible de charger la ressource pour le moment.");
                return;
            }
            fclose($outputHandler);

            // Check if all the file was upload
            if (filesize($outputFilepath) == $upload->file_size) {
                $this->success([
                    'completed' => true
                ]);
                return;
            } elseif (filesize($outputFilepath) > $upload->file_size) {
                $this->error(500, "La taille des données reçues dépasse la taille du fichier initial, veuillez recommencer le téléversement.");
                return;
            }

            // Calculate the chunkSize based on upload speed
            // The goal is to get a chunk every 3 seconds
            $deltaT = time() - min(intval($file_form->startedOn->getValue()), time());
            $_chunkSize = intval($file_form->chunkSize->getValue());

            if ($deltaT == 0) {
                $chunkSize = intval(self::MAX_CHUNK_SIZE*10**6);
            } else {
                $speed = $_chunkSize/$deltaT;
                $chunkSize = min(intval(self::MAX_CHUNK_SIZE*10**6), max(intval(self::MIN_CHUNK_SIZE*10**6), intval($speed*3)));
            }


            // Sending to the front end the next slice coordinate
            $this->success([
                'completed' => false,
                'startIndex' => filesize($outputFilepath),
                'chunkSize' => $chunkSize
            ]);
            return;
        }

        $this->error(400, "Votre fichier est invalide.");

    }

    /**
     * @param $video_token
     * @throws \Exception
     */
    public function end($video_token) {

        $videoManager = $this->getModelManager('video');
        $video = $videoManager->select(['token' => $video_token], true);

        if (!$video) {
            $this->error(404, "La ressource n'existe pas.");
            return;
        }

        // Check the video wasn't already uploaded
        if ($video->upload_status === ManagerModule::UPLOAD_STATUS["UPLOAD_END"]) {
            $this->error(400, "La ressource a déjà été téléversée.");
            return;
        }

        // Check if any upload was created
        $uploadManager = $this->getModelManager('video_upload');
        $upload = $uploadManager->select(['video_token' => $video->token], true);

        if (!$upload) {
            $this->error(400, "Le téléversement n'a pas été configuré.");
            return;
        }


        $fileManager = $this->getFileManager();
        $tempFileIdentifier = $upload->file_identifier;

        if (!$fileManager->file_exists($tempFileIdentifier)) {
            $this->error(400, "Le téléversement n'a pas été initialisé correctement.");
            return;
        }
        $outputFilepath = $fileManager->get($tempFileIdentifier);

        if (filesize($outputFilepath) != $upload->file_size) {
            $this->error(400, "Toute la ressource n'a pas été correctement téléversée. Veuillez recommencer.");
            return;
        }

        try {
            $video->file_identifier = $fileManager->move_file($outputFilepath, 'video');
            $video->upload_status = ManagerModule::UPLOAD_STATUS["UPLOAD_END"];
            $videoManager->update($video, [
                'uploaded_on' => 'NOW()'
            ]);
        } catch (\Exception $e) {
            $this->log($e, 'video_upload');
            $this->error(500, "Une erreur est survenue lors de la mise à jour de la base de données.");
            return;
        }

        $this->success(null);
    }

    /**
     * @param $video_token
     * @throws \Exception
     */
    public function reset($video_token) {
        $videoManager = $this->getModelManager('video');
        $video = $videoManager->select(['token' => $video_token], true);

        if (!$video) {
            $this->error(404, "La ressource n'existe pas.");
            return;
        }

        // Check the video wasn't already uploaded
        if ($video->upload_status === ManagerModule::UPLOAD_STATUS["UPLOAD_END"]) {
            $this->error(400, "La ressource a déjà été téléversée.");
            return;
        }

        // Check if any upload was created
        $uploadManager = $this->getModelManager('video_upload');
        $upload = $uploadManager->select(['video_token' => $video->token], true);

        if (!$upload) {
            $this->error(400, "Aucun téléversement n'a été initialisé.");
            return;
        }

        $fileManager = $this->getFileManager();

        if ($fileManager->file_exists($upload->file_identifier)) {
            if (!unlink($fileManager->get($upload->file_identifier))) {
                $this->error(500, "Impossible de réinitialiser le téléversement.");
                return;
            }
        }

        try {
            $uploadManager->delete($upload);

            // Update the video upload_status
            $video->upload_status = ManagerModule::UPLOAD_STATUS["UPLOAD_NULL"];
            $videoManager->update($video);
        } catch (\Exception $e) {
            $this->log($e, 'video_upload');
            $this->error(500, "Impossible de charger la ressource pour le moment.");
            return;
        }

        $this->success(null);
    }

}
