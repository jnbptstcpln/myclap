<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 04/08/2019
 * Time: 16:49
 */

namespace Plexus\Event;


use Plexus\Application;

class ApplicationLoaded extends AbstractEvent {

    protected $application;

    /**
     * ApplicationLoadedEvent constructor.
     * @param Application $application
     */
    public function __construct(Application $application) {
        $this->application = $application;
    }

    /**
     * @return Application
     */
    public function getApplication() {
        return $this->application;
    }
}