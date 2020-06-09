<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 04/08/2019
 * Time: 15:27
 */

namespace Plexus\Service\Renderer;


use Plexus\Application;
use Plexus\Component;
use Plexus\Event\ApplicationLoaded;
use Plexus\Event\EventManager;
use Plexus\Event\ModuleLoaded;
use Plexus\Exception\RenderException;
use Plexus\Service\AbstractService;
use Plexus\Session;
use Plexus\Utils\Path;
use Plexus\Utils\Text;

abstract class AbstractRendererWrapper extends AbstractService implements RendererWrapperInterface {

    /**
     * AbstractRendererWrapper constructor.
     * @param Application $application
     */
    public function __construct(Application $application) {
        parent::__construct('Renderer', $application);
    }

    /**
     *
     */
    protected function registrerEventListeners(EventManager $eventManager) {
        $eventManager->addEventListener(ModuleLoaded::class, function(ModuleLoaded $event) {
            $module = $event->getModule();
            $template_dirpath = Path::build($module->getModuleDirPath(), 'templates');
            if (!is_dir($template_dirpath)) {
                if (!mkdir($template_dirpath)) {
                    throw new \Exception(sprintf("Impossible de créer le dossier de templates '%s'", $template_dirpath));
                }
            }
            $this->addTemplateFolder($module->getName(), $template_dirpath);
        });
        $eventManager->addEventListener(ApplicationLoaded::class, function(ApplicationLoaded $event) {
            $this->registerExtensions();
        });
    }

    /**
     *
     */
    protected function registerExtensions() {
        // Reference to the application
        $this->addGlobal('app', $this->application);

        // Render Element
        $this->addFilter('render', function ($element, ...$options) {
            $html = "";
            try {
                if (method_exists($element, 'render')) {
                    $html = $element->render(...$options);
                    if (is_object($html) && get_class($html) == RenderRequest::class) {
                        $html = $this->render($html->getIdentifier(), $html->getData());
                    } elseif (!is_string($html)) {
                        throw new \TypeError(Text::format("Réponse invalide de la méthode '{}:render'", get_class($element)));
                    }
                } else {
                    throw new RenderException(Text::format("La méthode '{}:render' n'existe pas", get_class($element)));
                }
            } catch (\Throwable $e) {
                $this->application->log(new RenderException(Text::format("Impossible d'effectuer le rendu de l'élément"), $e->getCode(), $e));
                if ($this->application->environnement('dev')) {
                    $html = "[[ Impossible d'effectuer le rendu de l'élément ]]";
                } else {
                    $html = "";
                }
            }
            return $html;
        }, ['is_safe' => array('html')]);

        // URL Current
        $this->addFunction('current_url', function(...$params) {
            return $this->application->getRouter()->getRequest()->uri();
        });

        // URL Last
            $this->addFunction('last_url', function(...$params) {
            return Session::getLastURL();
        });

        // Uri creation
        $this->addFunction('route_url', function($routeName, ...$params) {
            return $this->application->getRouter()->buildRouteUrl($routeName, ...$params);
        });

        // Help to debug
        $this->addFunction('dump', function ($data) {
            return Text::format("<pre style='max-height: 200px; overflow-y: scroll;'>{}</pre>", var_export($data, true));
        }, ['is_safe' => array('html')]);
    }
}