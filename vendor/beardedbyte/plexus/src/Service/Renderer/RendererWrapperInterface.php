<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 04/08/2019
 * Time: 15:27
 */

namespace Plexus\Service\Renderer;


use Plexus\Exception\RenderException;

interface RendererWrapperInterface {

    /**
     * @param $template
     * @param $data
     * @return mixed
     * @throws RenderException
     */
    function render($template, $data=[]);

    /**
     * @param $name
     * @param $path
     * @return mixed
     */
    public function addTemplateFolder($name, $path);

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    function addGlobal($name, $value);

    /**
     * @param $name
     * @param $function
     * @param $options
     * @return mixed
     */
    function addFunction($name, $function, $options=[]);

    /**
     * @param $name
     * @param $function
     * @param $options
     * @return mixed
     */
    function addFilter($name, $function, $options=[]);
}