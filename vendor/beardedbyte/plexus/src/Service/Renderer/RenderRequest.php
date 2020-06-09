<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 04/08/2019
 * Time: 16:18
 */

namespace Plexus\Service\Renderer;


class RenderRequest {

    /**
     * @var $identifier
     */
    protected $identifier;

    /**
     * @var array
     */
    protected $data;

    /**
     * TemplateReference constructor.
     * @param $identifier
     * @param $data
     */
    public function __construct($identifier, $data) {
        $this->identifier = $identifier;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    /**
     * @return array
     */
    public function getData() {
        return $this->data;
    }

}