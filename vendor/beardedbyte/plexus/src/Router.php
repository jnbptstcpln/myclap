<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 01:32
 */

namespace Plexus;


use Plexus\DataType\Collection;
use Plexus\Exception\HttpException;

class Router {

    protected $application;

    protected $request;

    protected $response;

    protected $routes;

    protected $propagation_stopped = false;

    protected $current_route = null;

    protected $routes_matched = [];

    public function __construct(Application $application) {
        $this->application = $application;
        $this->request = Request::createFromGlobals();
        $this->response = new Response();
        $this->routes = new Collection();
    }

    /**
     * @throws \Exception
     */
    public function dispatch() {

        $matches = 0;

        foreach ($this->routes as $routeName => $route) {
            if (!$this->propagation_stopped) {
                $params = [];
                if ($route->matches($this->request->method(), explode('?', $this->request->uri())[0], $params)) {

                    $this->current_route = $route;

                    if (!$route->matchesAllPattern()) {
                        $matches += 1;
                        $this->routes_matched[] = $route;
                    }

                    try {
                        $route->getAction()(...array_values($params));
                    } catch (HttpException $httpException) {
                        $this->stopPropagation();
                        $this->response->setStatusCode($httpException->getCode());
                        $this->application->onHttpException($httpException);
                    }

                };
            }
        }

        $this->current_route = null;

        if (!$this->response->isSent()) {
            if ($this->response->getStatusCode() == 0) {
                if ($matches > 0) {
                    $this->response->setStatusCode(200);
                } else {
                    $this->response->setStatusCode(404);
                    $this->application->onHttpException(HttpException::createFromCode(404));
                }
            }
            $this->response->send();
        }
    }

    /**
     * @param $url
     * @param $stop_propagation
     * @throws \Exception
     */
    public function redirect($url, $stop_propagation=true) {
        if ($stop_propagation) {
            $this->stopPropagation();
        }
        $this->response->redirect($url);
    }

    /**
     * @param bool $stop_propagation
     * @throws \Exception
     */
    public function refresh($stop_propagation=true) {
        $this->redirect($this->request->uri(), $stop_propagation);
    }

    /**
     * @param $routeName
     * @param mixed ...$params
     * @return string
     * @throws \Exception
     */
    public function buildRouteUrl($routeName, ...$params) {
        $route = $this->getRoute($routeName);
        return $route->build_uri(...$params);
    }

    /**
     *
     */
    public function stopPropagation() {
        $this->propagation_stopped = true;
    }

    /**
     * @param Route $route
     * @return $this
     * @throws \Exception
     */
    public function addRoute(Route $route) {
        if ($this->routes->get($route->getName()) !== null) {
            throw new \Exception('Un modèle est déjà enregistré sous le nom "'.$route->getName().'".');
        }
        $this->routes->set($route->getName(), $route);

        return $this;
    }

    /**
     * @param string $name
     * @return Route
     * @throws \Exception
     */
    public function getRoute($name) {
        if ($this->routes->get($name) === null) {
            throw new \Exception('Aucun modèle de route nommé "'.$name.'" n\'a été trouvé.');
        }
        return $this->routes->get($name);
    }

    /**
     * @return Collection
     */
    public function getRoutes() {
        return $this->routes;
    }

    /**
     * @return Request
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * @return null|Route
     */
    public function getCurrentRoute() {
        return $this->current_route;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped() {
        return $this->propagation_stopped;
    }

}