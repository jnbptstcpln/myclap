<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 00:22
 */

namespace Plexus;


use Plexus\DataType\Collection;
use Plexus\Event\EventManager;
use Plexus\Event\ModuleLoaded;
use Plexus\Utils\Path;
use Plexus\Utils\RegExp;

class Module {

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var string
     */
    protected $module_name;

    /**
     * @var string
     */
    protected $module_dirpath;

    /**
     * @var Collection
     */
    protected $controlers;

    /**
     * @var Configuration
     */
    protected $routes_config;

    /**
     * Module constructor.
     * @param $name
     * @param Application $application
     * @throws \Exception
     */
    public function __construct($name, Application $application) {
        $this->module_name = $name;
        $this->application = $application;
        $this->controlers = new Collection();

        // ReflectionClass allow us to get info of the child class
        $classInfo = new \ReflectionClass($this);
        $this->module_dirpath = dirname($classInfo->getFileName());

        $this->deployFileStructure();

        // Getting the route config
        $this->routes_config = new Configuration('routes', $this->module_dirpath);

        $controlers_dirpath = Path::build($this->module_dirpath, 'Controlers');

        // Getting automatically all the controlers
        if (is_dir($controlers_dirpath)) {
            $controler_files = scandir($controlers_dirpath);
            if ($controler_files) {
                foreach ($controler_files as $controler_file) {
                    if ($controler_file == '.' || $controler_file == '..' || !RegExp::matches('/(.*)\.php$/', $controler_file)) {
                        continue;
                    }
                    $controler_name = str_replace('.php', '', $controler_file);
                    $controler_class = '\\'.$classInfo->getNamespaceName().'\\Controlers\\'.$controler_name;
                    if (!class_exists($controler_class)) {

                        throw new \Exception('Aucune classe nommée "'.$controler_class.'" n\'a été trouvée.');
                    }
                    $this->addControler(new $controler_class($controler_name,$this));
                }
            }
        }

        $this->registrerEventListeners($this->getEventManager());

        $this->application->getEventManager()->dispatch(new ModuleLoaded($this));
    }

    public function middleware() {}

    /**
     * @throws \Exception
     */
    public function deployFileStructure() {
        // Folders
        $folders = ['Controlers', 'Forms', 'Events'];
        foreach ($folders as $folder) {
            $path = Path::build($this->module_dirpath, $folder);
            if (!file_exists($path)) {
                if (!mkdir($path)) {
                    throw new \Exception(sprintf("Impossible de créer le répertoire '%s'", $path));
                }
            }
        }
        // Config
        $config_files = ['routes'];
        foreach ($config_files as $file) {
            $path = Path::build($this->module_dirpath, $file.'.yaml');
            if (!file_exists($path)) {
                if (!touch($path)) {
                    throw new \Exception(sprintf("Impossible de créer le fichier de configuration '%s'", $path));
                }
            }
        }
    }

    /**
     * @param EventManager $eventManager
     */
    protected function registrerEventListeners(EventManager $eventManager) {}

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
     * @param $data
     * @param $identifier
     */
    public function log($data, $identifier=null) {
        $this->getApplication()->log($data, $identifier);
    }

    /**
     * @param $message
     * @param $type
     */
    public function flash($message, $type=null) {
        $this->getApplication()->flash($message, $type);
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
     * @return mixed
     */
    public function getName() {
        return $this->module_name;
    }

    /**
     * @return string
     */
    public function getModuleDirPath() {
        return $this->module_dirpath;
    }

    /**
     * @return Collection
     * @throws \Exception
     */
    public function getRoutes() {
        return $this->routes_config->read();
    }

    /**
     * @param $name
     * @return Controler
     * @throws \Exception
     */
    public function getControler($name) {
        if ($this->controlers->get($name) === null) {
            throw new \Exception('Aucun contrôleur nommé "'.$name.'" n\'a été trouvé dans le module "'.$this->module_name.'".');
        }
        return $this->controlers->get($name);
    }

    /**
     * @param Controler $controler
     * @return Module
     * @throws \Exception
     */
    public function addControler(Controler $controler) {
        if ($this->controlers->get($controler->getName()) !== null) {
            throw new \Exception('Un contrôleur est déjà enregistré sous le nom "'.$controler->getName().'" '.'dans le module "'.$this->module_name.'".');
        }
        $this->controlers->set($controler->getName(), $controler);

        return $this;
    }

    /**
     * @return Application
     */
    public function getApplication() {
        return $this->application;
    }

    /**
     * @return Container
     */
    public function getContainer() {
        return $this->application->getContainer();
    }

    /**
     * @return Router
     */
    public function getRouter() {
        return $this->getContainer()->getRouter();
    }

    /**
     * @param $name
     * @return ModelManager
     * @throws \Exception
     */
    public function getModelManager($name) {
        return $this->getContainer()->getModelManager($name);
    }

    /**
     * @param $name
     * @return AbstractService
     * @throws \Exception
     */
    public function getService($name) {
        return $this->getContainer()->getService($name);
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
     * @return EventManager
     */
    public function getEventManager() {
        return $this->getContainer()->getEventManager();
    }
}