<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:57
 */

namespace Plexus\FormField;


use Plexus\DataType\Collection;
use Plexus\Error\FormError;
use Plexus\Validator\AbstractValidator;
use Plexus\Validator\FileSizeValidator;

class FileInput extends Input {

    /**
     * @var array
     */
    protected $file=null;

    /**
     * FileInput constructor.
     * @param $id
     * @param array $settings
     */
    public function __construct($id, $settings=[]) {
        parent::__construct($id, 'file', $settings);
        $this->addValidator(new FileSizeValidator($this->settings->get('maxsize')));
    }

    /**
     * @param Collection $settings
     * @return Collection
     */
    public function buildSetting(Collection $settings) {
        $collection = parent::buildSetting($settings);
        $collection->set('maxsize', $settings->get('maxsize'));
        return $collection;
    }

    /**
     * @return bool
     */
    private function _validate() {
        $this->errors = new Collection();

        if ($this->required) {
            if ($this->file['error'] === UPLOAD_ERR_NO_FILE) {
                $this->errors->push(new FormError("Veuillez envoyer un fichier."));
                return false;
            }
        }

        $this->validators->each(function($i, AbstractValidator $validator) {
            if (!$validator->validate($this->getValue())) {
                $this->errors->push($validator->getError());
            }
            return $validator->getStopValidation();
        });
        return ($this->errors->length() == 0);
    }

    /**
     * @param $value
     * @return $this|Input
     */
    public function setValue($value) {
        $this->file = isset($_FILES[$this->name]) ? $_FILES[$this->name] : null;
        return $this;
    }

    /**
     * @return array
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * @return bool
     */
    public function isFileValid() {
        return $this->file && $this->file['error'] === UPLOAD_ERR_OK;
    }

    /**
     * @return mixed
     */
    public function getFilepath() {
        return $this->file['tmp_name'];
    }


}