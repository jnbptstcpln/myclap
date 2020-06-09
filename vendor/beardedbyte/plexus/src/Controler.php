<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 00:53
 */

namespace Plexus;


use Plexus\Exception\HttpException;
use Plexus\Exception\RenderException;
use Plexus\Service\AbstractService;
use Plexus\Utils\Text;

class Controler {

    /**
     * @var Module
     */
    protected $module;

    /**
     * @var string
     */
    protected $name;

    /**
     * Controler constructor.
     * @param $name
     * @param Module $module
     */
    public function __construct($name, Module $module) {
        $this->name = $name;
        $this->module = $module;
    }

    /**
     *
     */
    public function middleware() {}

    /**
     * @param $routeName
     * @param mixed ...$params
     * @return string
     * @throws \Exception
     */
    public function buildRouteUrl($routeName, ...$params) {
        return $this->getRouter()->buildRouteUrl($routeName, ...$params);
    }

    /**
     * @param $message
     * @param $type
     */
    public function flash($message, $type=null) {
        $this->getApplication()->flash($message, $type);
    }

    /**
     * @param $template
     * @param array $data
     * @throws \Exception
     */
    public function render($template, $data=array()) {
        if (!$this->getContainer()->isService('Renderer')) {
            throw new RenderException(Text::format("Aucun service de rendu n'a été instancié."));
        }
        try {
            $this->getRouter()->getResponse()->append($this->getService('Renderer')->render($template, $data));
        } catch (\Exception $e) {
            throw new RenderException(Text::format("Une erreur est survenu lors du rendu du template '{}'", $template), $e->getCode(), $e);
        }
    }

    /**
     * @param $data
     * @param $identifier
     */
    public function log($data, $identifier=null) {
        $this->getApplication()->log($data, $identifier);
    }

    /**
     * @param null $is
     * @return bool|mixed|null
     */
    public function method($is=null) {
        return $this->getRequest()->method($is);
    }

    /**
     * @return DataType\Collection
     */
    public function paramsGet() {
        return $this->getRequest()->paramsGet();
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function paramGet($name, $default=null) {
        return $this->getRequest()->paramGet($name, $default);
    }

    /**
     * @return DataType\Collection
     */
    public function paramsPost() {
        return $this->getRequest()->paramsPost();
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function paramPost($name, $default=null) {
        return $this->getRequest()->paramPost($name, $default);
    }

    /**
     * @param $url
     * @param bool $stop_propagation
     * @throws \Exception
     */
    public function redirect($url, $stop_propagation=true) {
        $this->getRouter()->redirect($url, $stop_propagation);
    }

    /**
     * @param bool $stop_propagation
     * @throws \Exception
     */
    public function refresh($stop_propagation=true) {
        $this->getRouter()->refresh($stop_propagation);
    }

    /**
     * @param $json
     * @param bool $stop_propagation
     * @throws \Exception
     */
    public function json($json, $stop_propagation=true) {
        if ($stop_propagation) {
            $this->getRouter()->stopPropagation();
        }
        $this->getRouter()->getResponse()->json($json);
    }



    /**
     * @return Module
     */
    public function getModule() {
        return $this->module;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return Container
     */
    public function getContainer() {
        return $this->module->getContainer();
    }

    /**
     * @return Application
     */
    public function getApplication() {
        return $this->getContainer()->getApplication();
    }

    /**
     * @param $name
     * @return ModelManager
     * @throws \Exception
     */
    public function getModelManager($name) {
        return $this->module->getContainer()->getModelManager($name);
    }

    /**
     * @return Router
     */
    public function getRouter() {
        return $this->getContainer()->getRouter();
    }

    /**
     * @return Request
     */
    public function getRequest() {
        return $this->getRouter()->getRequest();
    }

    /**
     * @return Response
     */
    public function getResponse() {
        return $this->getRouter()->getResponse();
    }

    /**
     * @param $name
     * @return AbstractService
     * @throws \Exception
     */
    public function getService($name) {
        return $this->getContainer()->getService($name);
    }
}