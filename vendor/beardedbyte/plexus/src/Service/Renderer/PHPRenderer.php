<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 04/08/2019
 * Time: 15:27
 */

namespace Plexus\Service\Renderer;


use Plexus\Application;
use Plexus\Exception\RenderException;
use Plexus\Service\AbstractService;
use Plexus\Utils\Text;

class PHPRenderer extends AbstractRendererWrapper implements RendererWrapperInterface {

    /**
     * @var
     */
    protected $renderer;

    /**
     * PHPRenderer constructor.
     * @param Application $application
     * @param $template_folder
     * @throws \Exception
     */
    public function __construct(Application $application, $template_folder) {
        parent::__construct($application);
        $this->renderer = new Renderer($template_folder);
    }

    /**
     * @param $template
     * @param array $data
     * @return mixed|string
     * @throws \Throwable
     */
    public function render($template, $data=[]) {
        return $this->renderer->render($template, $data);
    }

    /**
     * @param $name
     * @param $path
     * @return mixed|void
     * @throws \Exception
     */
    public function addTemplateFolder($name, $path) {
        $this->renderer->addTemplateFolder($name, $path);
    }

    /**
     * @param $name
     * @param $value
     * @return mixed|void
     */
    public function addGlobal($name, $value) {
        $this->renderer->addGlobal($name, $value);
    }

    /**
     * @param $name
     * @param $function
     * @param $options
     * @return mixed|void
     */
    public function addFunction($name, $function, $options=[]) {
        $this->renderer->addFunction($name, $function, $options);
    }

    /**
     * @param $name
     * @param $function
     * @param $options
     * @return mixed|void
     */
    public function addFilter($name, $function, $options=[]) {
        $this->renderer->addFilter($name, $function, $options=[]);
    }
}