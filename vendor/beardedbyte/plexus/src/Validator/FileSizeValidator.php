<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\Validator;


use Plexus\Utils\Text;

class FileSizeValidator extends AbstractValidator {

    /**
     * @var int
     */
    protected $maxsize;

    /**
     * FileSizeValidator constructor.
     * @param null $maxsize
     */
    public function __construct($maxsize=null) {
        $this->maxsize = $maxsize;
        parent::__construct(Text::format("Votre fichier dépasse la taille maximale autorisée ({}).", $this->getMaxSizeLabel()), function($file) {
            if ($file) {
                if ($this->maxsize !== null) {
                    if (filesize($file['tmp_name']) > $this->maxsize) {
                        return false;
                    }
                }
                return !($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE);
            }
            // If no file was sent, the validator should not produce any error because the file's size is 0
            // Thus, it returns true to validate the input
            return true;
        });
    }

    protected function getMaxSizeLabel() {
        if ($this->maxsize < intval(1e3)) {
            return Text::format("{} octets", $this->maxsize);
        }
        if ($this->maxsize < intval(1e6)) {
            return Text::format("{} Ko", intval($this->maxsize/1e3));
        }
        if ($this->maxsize < intval(1e9)) {
            return Text::format("{} Mo", intval($this->maxsize/1e6));
        }
        return Text::format("{} Go", intval($this->maxsize/1e9));
    }
}