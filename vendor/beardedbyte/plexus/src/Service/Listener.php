<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 05/08/2019
 * Time: 00:57
 */

namespace Plexus\Service;


use Plexus\Event\AbstractEvent;
use Plexus\Event\EventManager;

class Listener extends AbstractService {

    /**
     *
     */
    protected function registrerEventListeners(EventManager $eventManager) {
        $eventManager->addEventListener(AbstractEvent::class, function (AbstractEvent $event) {
            return $this->handleEvent($event);
        });
    }

    /**
     * @param AbstractEvent $event
     */
    protected function handleEvent(AbstractEvent $event) {

    }
}