<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 31/12/2019
 * Time: 16:12
 */

namespace myCLAP\Services;


use myCLAP\Service;

class ImageLib extends Service {

    /**
     * @param $filepath
     * @param bool $isIdentifier
     * @return int
     * @throws \Exception
     * @throws \Plexus\Exception\BundleException
     * @throws \Plexus\Exception\FileException
     */
    public function type($filepath, $isIdentifier=true) {
        if ($isIdentifier) {
            $fileManager = $this->getFileManager();
            $filepath = $fileManager->get($filepath);
        }
        return exif_imagetype($filepath);
    }

    /**
     * @param $file_identifier
     * @return bool
     * @throws \Exception
     */
    public function isJPEG($file_identifier) {
        return $this->type($file_identifier) == IMAGETYPE_JPEG;
    }

    /**
     * @param $file_identifier
     * @return bool
     * @throws \Exception
     * @throws \Plexus\Exception\BundleException
     * @throws \Plexus\Exception\FileException
     */
    public function isPNG($file_identifier) {
        return $this->type($file_identifier) == IMAGETYPE_JPEG;
    }

    /**
     * @param $input_identifier
     * @param $output_bundle
     * @param $width
     * @param $height
     * @return string
     * @throws \Exception
     * @throws \Plexus\Exception\BundleException
     * @throws \Plexus\Exception\FileException
     * @throws \TypeError
     */
    public function resize($input_identifier, $output_bundle, $width, $height) {

        $fileManager = $this->getFileManager();
        $inputPath = $fileManager->get($input_identifier);
        // Create the output file
        $outputIdentifier = $fileManager->create_file($output_bundle);
        $outputPath = $fileManager->get($outputIdentifier);

        list($_width, $_height) = getimagesize($inputPath);

        if ($_width >= $_height) {
            $newWidth = $width;
            $newHeight = ($_height / $_width) * $width;
        } else {
            $newHeight = $height;
            $newWidth = ($_width / $_height) * $height;
        }

        // Creates an initial image in memory with calculated measures
        $tempImage = imagecreatetruecolor(ceil($newWidth), ceil($newHeight));
        $buffer = null;
        $this->getContainer()->getApplication()->log('switch');
        switch ($this->type($input_identifier)) {
            case IMAGETYPE_JPEG:
                $buffer = imagecreatefromjpeg($inputPath);
                imagecopyresampled($tempImage, $buffer, 0, 0, 0, 0, ceil($newWidth), ceil($newHeight), ceil($_width), ceil($_height));
                imagejpeg($tempImage, $outputPath);
                break;
            case IMAGETYPE_PNG:
                $buffer = imagecreatefrompng($inputPath);
                $this->getContainer()->getApplication()->log(imagecreatefrompng($inputPath) === false ? 'erreur' : 'ok');
                imagealphablending($tempImage, false);
                imagesavealpha($tempImage,true);
                $transparent = imagecolorallocatealpha($tempImage, 255, 255, 255, 127);
                imagefilledrectangle($buffer, 0, 0, $newWidth, $newHeight, $transparent);
                imagecopyresampled($tempImage, $buffer, 0, 0, 0, 0, ceil($newWidth), ceil($newHeight), ceil($_width), ceil($_height));
                imagepng($tempImage, $outputPath);
                break;
            default:
                throw new \TypeError("L'image doit être au format PNG ou JPEG");
        }

        return $outputIdentifier;
    }


}