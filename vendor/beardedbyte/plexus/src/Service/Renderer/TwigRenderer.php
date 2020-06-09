<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 04/08/2019
 * Time: 15:27
 */

namespace Plexus\Service\Renderer;


use Plexus\Application;

class TwigRenderer extends AbstractRendererWrapper implements RendererWrapperInterface {

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var \Twig_Loader_Filesystem
     */
    protected $twig_loader;

    /**
     * TwigRenderer constructor.
     * @param Application $application
     * @param $template_folder
     */
    public function __construct(Application $application, $template_folder) {
        parent::__construct($application);
        $this->twig_loader = new \Twig_Loader_Filesystem($template_folder);
        $this->twig = new \Twig_Environment($this->twig_loader);
    }

    /**
     * @param $template
     * @param array $data
     * @return mixed
     */
    public function render($template, $data=[]) {
        return $this->twig->render($template, $data);
    }

    /**
     * @param $name
     * @param $path
     * @return mixed|void
     */
    public function addTemplateFolder($name, $path) {
        $this->twig_loader->addPath($path, $name);
    }

    /**
     * @param $name
     * @param $value
     */
    public function addGlobal($name, $value) {
        $this->twig->addGlobal($name, $value);
    }

    /**
     * @param $name
     * @param $function
     * @param $options
     */
    public function addFunction($name, $function, $options=[]) {
        $this->twig->addFunction(new \Twig\TwigFunction($name, $function, $options));
    }

    /**
     * @param $name
     * @param $function
     * @param $options
     */
    public function addFilter($name, $function, $options=[]) {
        $this->twig->addFilter(new \Twig\TwigFilter($name, $function, $options));
    }
}