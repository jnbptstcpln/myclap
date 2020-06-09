<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 04/08/2019
 * Time: 16:49
 */

namespace Plexus\Event;



use Plexus\Module;

class ModuleLoaded extends AbstractEvent {

    /**
     * @var Module
     */
    protected $module;

    /**
     * ModulesLoaded constructor.
     * @param Module $module
     */
    public function __construct(Module $module) {
        $this->module = $module;
    }

    /**
     * @return Module
     */
    public function getModule() {
        return $this->module;
    }
}