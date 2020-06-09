<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 31/07/2019
 * Time: 22:13
 */

namespace Plexus;


use Plexus\DataType\Collection;
use Plexus\Event\EventManager;
use Plexus\Service\AbstractService;
use Plexus\Utils\Path;

class Container {

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var Collection
     */
    protected $databases;

    /**
     * @var Collection
     */
    protected $modelmanagers;

    /**
     * @var Collection
     */
    protected $modules;

    /**
     * @var Collection
     */
    protected $configurations;

    /**
     * @var Collection
     */
    protected $services;

    public function __construct(Application $application) {
        $this->application = $application;

        $this->router = new Router($application);
        $this->eventManager = new EventManager($application);
        $this->databases = new Collection();
        $this->modelmanagers = new Collection();
        $this->modules = new Collection();
        $this->configurations = new Collection();
        $this->services = new Collection();
    }

    /**
     * @return Application
     */
    public function getApplication() {
        return $this->application;
    }

    /**
     * @return Router
     */
    public function getRouter() {
        return $this->router;
    }

    /**
     * @return EventManager
     */
    public function getEventManager() {
        return $this->eventManager;
    }

    /**
     * @param string $name
     * @param \PDO $database
     * @return Container
     * @throws \Exception
     */
    public function addDatabase($name, \PDO $database) {
        if ($this->databases->isset($name)) {
            throw new \Exception('Une base de données est déjà enregistrée sous le nom "'.$name.'".');
        }
        $this->databases->set($name, $database);

        return $this;
    }

    /**
     * @param string $name
     * @return \PDO
     * @throws \Exception
     */
    public function getDatabase($name) {
        if (!$this->databases->isset($name)) {
            throw new \Exception('Aucune base de données nommée "'.$name.'" n\'a été trouvée.');
        }
        return $this->databases->get($name);
    }

    /**
     * @return Collection
     */
    public function getDatabases() {
        return $this->databases;
    }

    /**
     * @param ModelManager $modelmanager
     * @return Container
     * @throws \Exception
     */
    public function addModelManager(ModelManager $modelmanager) {
        if ($this->modelmanagers->isset($modelmanager->getName())) {
            throw new \Exception('Un modèle est déjà enregistré sous le nom "'.$modelmanager->getName().'".');
        }
        $this->modelmanagers->set($modelmanager->getName(), $modelmanager);

        return $this;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function isModelManager($name) {
        return $this->modelmanagers->isset($name);
    }

    /**
     * @param string $name
     * @return ModelManager
     * @throws \Exception
     */
    public function getModelManager($name) {
        if (!$this->modelmanagers->isset($name)) {
            throw new \Exception('Aucun modèle nommé "'.$name.'" n\'a été trouvé.');
        }
        return $this->modelmanagers->get($name);
    }

    /**
     * @return Collection
     */
    public function getModelManagers() {
        return $this->modelmanagers;
    }

    /**
     * @param Module $module
     * @return Container
     * @throws \Exception
     */
    public function addModule(Module $module) {
        if ($this->modules->isset($module->getName())) {
            throw new \Exception('Un module est déjà enregistré sous le nom "'.$module->getName().'".');
        }
        $this->modules->set($module->getName(), $module);

        return $this;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function isModule($name) {
        return $this->modules->isset($name);
    }

    /**
     * @param string $name
     * @return Module
     * @throws \Exception
     */
    public function getModule($name) {
        if (!$this->modules->isset($name)) {
            throw new \Exception('Aucun module nommé "'.$name.'" n\'a été trouvé.');
        }
        return $this->modules->get($name);
    }

    /**
     * @return Collection
     */
    public function getModules() {
        return $this->modules;
    }

    /**
     * @param Configuration $configuration
     * @param bool $override
     * @return $this
     * @throws \Exception
     */
    public function addConfiguration(Configuration $configuration, $override=false) {
        if ($this->configurations->isset($configuration->getName()) && !$override) {
            throw new \Exception('Un fichier de configuration est déjà enregistré sous le nom "'.$configuration->getName().'".');
        }
        $this->configurations->set($configuration->getName(), $configuration);

        return $this;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function isConfiguration($name) {
        return $this->configurations->isset($name);
    }

    /**
     * @param string $name
     * @return Configuration
     * @throws \Exception
     */
    public function getConfiguration($name) {
        if (!$this->configurations->isset($name)) {
            throw new \Exception('Aucun fichier de configuration nommé "'.$name.'" n\'a été trouvé.');
        }
        return $this->configurations->get($name);
    }

    /**
     * @return Collection
     */
    public function getConfigurations() {
        return $this->configurations;
    }

    /**
     * @param AbstractService $service
     * @return Container
     * @throws \Exception
     */
    public function addService(AbstractService $service) {
        if ($this->services->isset($service->getName())) {
            throw new \Exception('Un service est déjà enregistré sous le nom "'.$service->getName().'".');
        }
        $this->services->set($service->getName(), $service);

        return $this;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function isService($name) {
        return $this->services->isset($name);
    }

    /**
     * @param string $name
     * @return AbstractService
     * @throws \Exception
     */
    public function getService($name) {
        if (!$this->services->isset($name)) {
            throw new \Exception('Aucun service nommé "'.$name.'" n\'a été trouvé.');
        }
        return $this->services->get($name);
    }

    /**
     * @return Collection
     */
    public function getServices() {
        return $this->services;
    }

}