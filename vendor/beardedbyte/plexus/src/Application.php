<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 31/07/2019
 * Time: 22:07
 */

namespace Plexus;


use Plexus\DataType\Collection;
use Plexus\Event\AbstractEvent;
use Plexus\Event\ApplicationLoaded;
use Plexus\Event\ConfigurationsLoaded;
use Plexus\Event\ConfigurationsLoading;
use Plexus\Event\DatabasesLoaded;
use Plexus\Event\DatabasesLoading;
use Plexus\Event\Listener;
use Plexus\Event\ModelManagersLoaded;
use Plexus\Event\ModelManagersLoading;
use Plexus\Event\ModulesLoaded;
use Plexus\Event\ModulesLoading;
use Plexus\Event\RoutesLoaded;
use Plexus\Event\RoutesLoading;
use Plexus\Event\ServicesLoaded;
use Plexus\Event\ServicesLoading;
use Plexus\Exception\HttpException;
use Plexus\Service\AbstractService;
use Plexus\Utils\Logger;
use Plexus\Utils\Path;

class Application {

    /**
     * @var bool
     */
    protected $loaded = false;

    /**
     * @var bool
     */
    protected $running = false;

    /**
     * @var string
     */
    protected $root_path;

    /**
     * @var Container
     */
    protected $container;

    /**
     * Application constructor.
     * @param $root_path
     * @param callable $eventRegistration
     */
    function __construct($root_path, callable $eventRegistration=null) {
        try {
            $this->root_path = Path::normalize(realpath($root_path));
            $this->container = new Container($this);

            if (is_callable($eventRegistration)) {
                $eventRegistration($this->getEventManager());
            }

            $this->deployFileStructure();
            $this->loadConfigurations();
            $this->loadServices();
            $this->loadDatabases();
            $this->loadModelManagers();
            $this->loadModules();
            $this->loadRoutes();

            $this->loaded = true;
            $this->getEventManager()->dispatch(new ApplicationLoaded($this));
        } catch (\Throwable $e) {
            $this->onThrow($e);
        }
    }

    /**
     *
     */
    public function run() {
        if ($this->loaded) {
            try {
                $this->getRouter()->dispatch();
            } catch (\Throwable $e) {
                $this->onThrow($e);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function deployFileStructure() {
        // Folders
        $folders = ['config', 'log', 'src', 'src/Services', 'src/Modules', 'src/config', 'public'];
        foreach ($folders as $folder) {
            $path = Path::build($this->root_path, $folder);
            if (!file_exists($path)) {
                if (!mkdir($path)) {
                    throw new \Exception(sprintf("Impossible de créer le répertoire '%s'", $path));
                }
            }
        }

        // Files
        $config_files = ['config/databases.yaml', 'config/environment.yaml', 'src/config/routes.yaml', 'src/config/modelmanagers.yaml', 'src/config/services.yaml'];
        foreach ($config_files as $file) {
            $path = Path::build($this->root_path, $file);
            if (!file_exists($path)) {
                if (!touch($path)) {
                    throw new \Exception(sprintf("Impossible de créer le fichier de configuration '%s'", $path));
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function loadConfigurations() {
        $this->getEventManager()->dispatch(new ConfigurationsLoading());
        // config/
        $config_path = Path::build($this->getRootPath(), 'config');
        if (is_dir($config_path)) {
            $files = scandir($config_path);
            if ($files) {
                foreach ($files as $file) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    $config_name = str_replace('.yaml', '', $file);
                    $this->container->addConfiguration(new Configuration($config_name, $config_path));
                }
            }
        }
        // src/config/
        $config_path = Path::build($this->getRootPath(), 'src', 'config');
        if (is_dir($config_path)) {
            $files = scandir($config_path);
            if ($files) {
                foreach ($files as $file) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    $config_name = str_replace('.yaml', '', $file);
                    $configuration = new Configuration($config_name, $config_path);
                    if ($this->container->getConfigurations()->isset($config_name)) {
                        $this->container->getConfiguration($config_name)->appendConfiguration($configuration);
                    } else {
                        $this->container->addConfiguration($configuration);
                    }
                }
            }
        }
        $this->getEventManager()->dispatch(new ConfigurationsLoaded());
    }

    /**
     * @throws \Exception
     */
    private function loadServices() {
        $this->getEventManager()->dispatch(new ServicesLoading());

        // ReflectionClass allow us to get info of the child class
        $classInfo = new \ReflectionClass($this);
        $src_path = dirname($classInfo->getFileName());
        $services_dir_path = Path::build($src_path, 'Services');
        // Getting automatically all the services
        if (is_dir($services_dir_path)) {
            $files = scandir($services_dir_path);
            if ($files) {
                foreach ($files as $file) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    $service_name = str_replace('.php', '', $file);
                    $service_class = sprintf('\\%s\\Services\\%s', $classInfo->getNamespaceName(), $service_name);
                    if (!class_exists($service_class)) {
                        throw new \Exception('Aucune classe nommée "'.$service_class.'" n\'a été trouvée lors du chargement des services.');
                    }
                    $this->container->addService(new $service_class($service_name,$this));
                }
            }
        }

        $this->getEventManager()->dispatch(new ServicesLoaded());
    }

    /**
     * @throws \Exception
     */
    private function loadDatabases() {
        $this->getEventManager()->dispatch(new DatabasesLoading());

        $dbConfig = $this->container->getConfiguration('databases')->read();
        $dbConfig->each(function($dbName, $config) {
            if (isset($config->path)) {
                $path = $config->path;
            } else {
                $path = sprintf("%s:host=%s:%s;dbname=%s;charset=utf8",
                    $config->type,
                    $config->host,
                    $config->port,
                    $config->database
                );
            }
            $this->container->addDatabase($dbName, new \PDO($path, $config->username, $config->password));
        });

        $this->getEventManager()->dispatch(new DatabasesLoaded());
    }

    /**
     * @throws \Exception
     */
    private function loadModelManagers() {
        $this->getEventManager()->dispatch(new ModelManagersLoading());

        $dbConfig = $this->container->getConfiguration('databases')->read();
        $mmConfig = $this->container->getConfiguration('modelmanagers')->read();
        $mmConfig->each(function($dbName, Collection $modelManagers) use ($dbConfig) {
            $dbType = $dbConfig->get($dbName)->type;
            $mmClass = ModelManager::class;
            switch ($dbType) {
                case 'mysql':
                    $mmClass = ModelManager_MySQL::class;
                    break;
            }
            $modelManagers->each(function($index, $modelManagerName) use ($dbName, $mmClass) {
                $this->container->addModelManager(new $mmClass($this->container->getDatabase($dbName), $modelManagerName, true));
            });
        });

        $this->getEventManager()->dispatch(new ModelManagersLoaded());
    }

    /**
     * @throws \Exception
     */
    private function loadModules() {
        $this->getEventManager()->dispatch(new ModulesLoading());

        // ReflectionClass allow us to get info of the child class
        $classInfo = new \ReflectionClass($this);
        $src_path = dirname($classInfo->getFileName());
        $modules_dir_path = Path::build($src_path, 'Modules');
        // Getting automatically all the modules
        if (is_dir($modules_dir_path)) {
            $files = scandir($modules_dir_path);
            if ($files) {
                foreach ($files as $file) {
                    if ($file == '.' || $file == '..' || !preg_match('/.*Module$/', $file)) {
                        continue;
                    }

                    $module_name = $file;
                    $module_class = sprintf('\\%s\\Modules\\%s\\%s', $classInfo->getNamespaceName(), $module_name, $module_name);
                    if (!class_exists($module_class)) {
                        throw new \Exception('Aucune classe nommée "'.$module_class.'" n\'a été trouvée lors du chargement des modules.');
                    }
                    $this->container->addModule(new $module_class($module_name,$this));
                }
            }
        }

        $this->getEventManager()->dispatch(new ModulesLoaded());
    }

    /**
     * @throws \Exception
     */
    public function loadRoutes() {
        $this->getEventManager()->dispatch(new RoutesLoading());

        // Get the routes defined in the config folder
        $routesConfig = $this->container->getConfiguration('routes')->read();
        $routesConfig->each(function($routeName, $route) {
            $route->name = $routeName;
            $this->registerRoute($route);
        });

        // Get the routes defined inside modules
        $this->getContainer()->getModules()->each(function($name, Module $module) {
            $routesConfig = $module->getRoutes();
            $routesConfig->each(function($routeName, $route) {
                $route->name = $routeName;
                $this->registerRoute($route);
            });
        });

        $this->getEventManager()->dispatch(new RoutesLoaded());
    }

    /**
     * @param $route
     * @throws \Exception
     */
    public function registerRoute($route) {
        $route = new Collection($route);
        $router = $this->getRouter();
        $components = Route::parse_action_identifier($route->action);
        $module = $this->container->getModule($components['module']);
        $controler = $module->getControler($components['controler']);
        $action = $components['action'];

        if (!method_exists($controler, $action)) {
            throw new \Exception('Aucune action nommée "'.$action.'" n\'existe dans le contrôleur "'.$components['module'].':'.$components['controler'].'"');
        }
        $router->addRoute(new Route($route->name, $route->get('method', '*'), $route->path, function(...$params) use ($module, $controler, $action) {
            $module->middleware();
            if (!$this->getRouter()->isPropagationStopped()) {
                $controler->middleware();
            }
            if (!$this->getRouter()->isPropagationStopped()) {
                $controler->$action(...$params);
            }
        }));
    }

    /**
     * @param AbstractEvent $event
     */
    public function dispatch(AbstractEvent $event) {
        $this->container->getEventManager()->dispatch($event);
    }

    /**
     * @param $message
     * @param string $type
     * @param array $params
     */
    public function flash($message, $type='info', $params=[]) {
        Session::flash($message, $type);
    }

    /**
     * @param null $type
     * @return array
     */
    public function flashes($type=null) {
        return Session::flashes($type);
    }

    /**
     * @param $identifier
     * @param bool $multiple
     * @return string
     */
    public function csrf_token($identifier, $multiple=false) {
        return Session::prepare_csrf_token($identifier, $multiple);
    }

    /**
     * @param $data
     * @param null $identifier
     */
    public function log($data, $identifier=null) {
        Logger::log($data, $identifier, $this->root_path);
    }

    /**
     * @param null $type
     * @return bool|mixed|null
     */
    public function environnement($type=null) {
        try {
            $envConfiguration = $this->container->getConfiguration('environment')->read();
            $env = $envConfiguration->get('env', 'prod');
            if ($type === null) {
                return $env;
            }
            return strcasecmp($env, $type) == 0;
        } catch (\Exception $e) {
            if ($type === null) {
                return 'prod';
            } else {
                return $type == 'prod';
            }
        }
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null|Collection
     * @throws \Exception
     */
    public function getEnvironmentVar($name, $default=null) {
        $envConfiguration = $this->container->getConfiguration('environment')->read();
        return $envConfiguration->get($name, $default);
    }

    /**
     * @param \Throwable $e
     */
    public function onThrow(\Throwable $e) {
        $this->log($e);
        if ($this->environnement('dev')) {
            if ($this->getRouter()->getRequest()->header('Accept') != 'application/json') {
                $output = sprintf("<div style='padding-left: 10px; border-left: 2px solid black'><h2>%s</h2><h3>%s</h3><h4>%s:%s</h4><p>%s</p>",
                    get_class($e),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    nl2br($e->getTraceAsString()
                    ));

                $acc = 1;
                $previous = $e->getPrevious();
                while ($previous !== null && $acc < 10) {
                    $output .= sprintf("<div style='padding-left: 10px; border-left: 2px solid black'><h2>%s</h2><h3>%s</h3><h4>%s:%s</h4><p>%s</p>",
                        get_class($previous),
                        $previous->getMessage(),
                        $previous->getFile(),
                        $previous->getLine(),
                        nl2br($previous->getTraceAsString()
                        ));
                    $previous = $previous->getPrevious();
                    $acc += 1;
                }

                for ($i=0;$i<$acc;$i++) {
                    $output .= '</div>';
                }

                echo sprintf("<body style='background-color: #ecf0f1'><h1 style='text-align: center;'>Oups... Une erreur est survenue :(</h1><div style='max-width: 1000px; margin: auto;'>%s</div></body>",
                    $output
                );
                return;
            }
        }

        $this->onHttpException(HttpException::createFromCode(500));
    }

    /**
     * @param HttpException $e
     */
    public function onHttpException(HttpException $e) {
        if ($this->getRouter()->getRequest()->header('Accept') != 'application/json') {
            try {
                $this->renderHttpException($e);
            } catch (\Throwable $exception) {
                echo sprintf("<body style='background-color: #ecf0f1'><h1 style='color: #2c3e50; text-align: center; margin-top: 5vh; font-family: sans-serif; max-width: 450px; margin-left: auto; margin-right: auto;'>HTTP Error : %d</h1></body>", $e->getCode());
            }
        }
    }

    /**
     * @param HttpException $e
     */
    public function renderHttpException(HttpException $e) {
        echo sprintf("<body style='background-color: #ecf0f1'><h1 style='color: #2c3e50; text-align: center; margin-top: 5vh; font-family: sans-serif; max-width: 450px; margin-left: auto; margin-right: auto;'>HTTP Error : %d</h1></body>", $e->getCode());
    }

    /**
     * @return string
     */
    public function getRootPath() {
        return $this->root_path;
    }

    /**
     * @return Container
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * @return Router
     */
    public function getRouter() {
        return $this->container->getRouter();
    }

    /**
     * @return Event\EventManager
     */
    public function getEventManager() {
        return $this->container->getEventManager();
    }

    /**
     * @param AbstractService $service
     * @return $this
     * @throws \Exception
     */
    public function addService(AbstractService $service) {
        $this->container->addService($service);
        return $this;
    }

    /**
     * @return Collection
     */
    public function getServices() {
        return $this->container->getServices();
    }

}