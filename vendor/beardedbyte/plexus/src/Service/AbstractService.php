<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 02/08/2019
 * Time: 21:59
 */

namespace Plexus\Service;


use Plexus\Application;
use Plexus\Container;
use Plexus\Event\EventManager;
use Plexus\Event\Listener;

abstract class AbstractService {

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var string
     */
    protected $name;

    /**
     * Service constructor.
     * @param $name
     * @param Application $application
     */
    public function __construct($name, Application $application) {
        $this->name = $name;
        $this->application = $application;

        $this->registrerEventListeners($this->getEventManager());
    }

    /**
     * @param EventManager $eventManager
     */
    protected function registrerEventListeners(EventManager $eventManager) {}

    /**
     * @return mixed|null|\Plexus\DataType\Collection
     * @throws \Exception
     */
    public function getConfiguration() {
        try {
            if (!$this->getContainer()->getConfiguration('services')->read()->isset(strtolower($this->name))) {
                throw new \Exception();
            }
            return $this->getContainer()->getConfiguration('services')->read()->get(strtolower($this->name));
        } catch (\Exception $e) {
            throw new \Exception(sprintf("Impossible de récupérer la configuration du service '%s'", strtolower($this->name)));
        }
    }

    /**
     * @return Container
     */
    public function getContainer() {
        return $this->application->getContainer();
    }

    /**
     * @return \Plexus\Event\EventManager
     */
    public function getEventManager() {
        return $this->getContainer()->getEventManager();
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

}