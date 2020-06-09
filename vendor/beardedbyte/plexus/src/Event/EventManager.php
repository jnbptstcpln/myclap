<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 04/08/2019
 * Time: 16:52
 */

namespace Plexus\Event;


use Plexus\Application;
use Plexus\DataType\Collection;
use Plexus\Exception\StopPropagationException;

class EventManager {

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var Collection
     */
    protected $listeners;

    /**
     * EventManager constructor.
     * @param Application $application
     */
    public function __construct(Application $application) {
        $this->application = $application;
        $this->listeners = new Collection();
    }

    /**
     * @param $event_class
     * @param callable $function
     */
    public function addEventListener($event_class, callable $callback) {
        $listeners = $this->listeners->get($event_class, new Collection());
        $listeners->push($callback);
        $this->listeners->set($event_class, $listeners);
    }

    /**
     * @param AbstractEvent $event
     */
    public function dispatch(AbstractEvent $event) {
        try {
            $this->listeners->each(function ($eventClass, Collection $listeners) use ($event) {
                if (get_class($event) == $eventClass || is_subclass_of($event, $eventClass)) {
                    $listeners->each(function($i, callable $callback) use ($event) {
                        $callback($event);
                    });
                }
            });
        } catch (StopPropagationException $exception) {

        }
    }

}