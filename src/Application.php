<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 24/12/2019
 * Time: 19:08
 */

namespace myCLAP;


use myCLAP\Services\RendererExtension;
use Plexus\Event\ApplicationLoaded;
use Plexus\Event\EventManager;
use Plexus\Event\ServicesLoading;
use Plexus\Exception\HttpException;
use Plexus\Service\AssetsManager;
use Plexus\Service\FileManager;
use Plexus\Service\Renderer\RendererWrapperInterface;
use Plexus\Service\Renderer\TwigRenderer;
use Plexus\Session;
use Plexus\Utils\Path;

class Application extends \Plexus\Application {

    public function __construct($root_path)
    {
        parent::__construct($root_path, function(EventManager $eventManager) {
            $eventManager->addEventListener(ServicesLoading::class, function(ServicesLoading $event) {
                $this->addService(new TwigRenderer($this, Path::build($this->getRootPath(), 'src', 'templates')));
                $this->addService(new AssetsManager($this));
                $this->addService(new FileManager($this));
            });
        });
    }

    public function onHttpException(HttpException $e) {

        $request = $this->getRouter()->getRequest();
        $response = $this->getRouter()->getResponse();

        $response->setStatusCode($e->getCode());

        switch ($request->header('Accept')) {
            case "application/json":
                $this->onHttpExceptionJSON($e);
                break;
            case 'text/html':
            default:
                $this->onHttpExceptionHTML($e);

        }

    }

    /**
     * @param HttpException $e
     */
    public function onHttpExceptionJSON(HttpException $e) {
        switch ($e->getCode()) {
            case 200:
                return;
            case 401:
                echo json_encode([
                    'status' => 401,
                    'success' => false,
                    'message' => "Vous devez être connecté pour accéder à cette page."
                ]);
                break;
            case 403:
                echo json_encode([
                    'status' => 404,
                    'success' => false,
                    'message' => "Vous n'avez pas le droit d'accéder à cette page."
                ]);
                break;
            case 404:
                echo json_encode([
                    'status' => 404,
                    'success' => false,
                    'message' => "La page à laquelle vous tentez d'accéder n'existe pas."
                ]);
                break;
            case 500:
            default:
                echo json_encode([
                    'status' => 500,
                    'success' => false,
                    'message' => "La page à laquelle vous tentez d'accéder n'existe pas."
                ]);
                break;
        }
    }

    /**
     * @param HttpException $e
     */
    public function onHttpExceptionHTML(HttpException $e) {
        try {
            switch ($e->getCode()) {
                case 200:
                    return;
                case 401:
                    echo $this->getRenderer()->render("/http/http-401.html.twig");
                break;
                case 403:
                    echo $this->getRenderer()->render("/http/http-403.html.twig");
                    break;
                case 404:
                    echo $this->getRenderer()->render("/http/http-404.html.twig");
                    break;
                case 500:
                default:
                    echo $this->getRenderer()->render("/http/http-xxx.html.twig", [
                        'code' => $e->getCode()
                    ]);
            }
        } catch (\Exception $_e) {
            parent::renderHttpException($e);
        }
    }

    /**
     * @return \Plexus\Service\AbstractService|RendererWrapperInterface
     * @throws \Exception
     */
    protected function getRenderer() {
        return $this->container->getService('Renderer');
    }

}