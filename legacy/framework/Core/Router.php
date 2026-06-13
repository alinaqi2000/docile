<?php

namespace Docile\Core;

use Docile\Docile;
use Docile\Http\Request;
use Docile\Support\Str;
use Exception;
use Closure;
// ...

class Router extends Docile
{
    private $routes = [];
    private $models = [];
    private $matchedRoute = "";
    private $recentlyAddedRoute = [];

    public function middlewares($middlewares, $redirects = [])
    {
        $that = static::getInstance();

        if (is_array($middlewares)) {
            foreach ($middlewares as $key => $middleware) {
                $that->routes[$that->recentlyAddedRoute['method']][$that->recentlyAddedRoute['route']]['middlewares'][] = ['name' => $middleware, "redirect" => $redirects[$key]];
            }
        }
    }
    public static function get(string $route, string | Closure $controller)
    {
        $that = static::getInstance();

        $that->routes['GET'][$route] = ['controller' => $controller];
        $that->recentlyAddedRoute = ['method' => "GET", "route" => $route, 'controller' => $controller];

        return $that;
    }

    public static function post(string $route, string | Closure $controller)
    {
        $that = static::getInstance();

        $that->routes['POST'][$route] = ['controller' => $controller];
        $that->recentlyAddedRoute = ['method' => "POST", "route" => $route, 'controller' => $controller];

        return $that;
    }
    public static function put(string $route, string | Closure $controller)
    {
        $that = static::getInstance();

        $that->routes['PUT'][$route] = ['controller' => $controller];
        $that->recentlyAddedRoute = ['method' => "PUT", "route" => $route, 'controller' => $controller];

        return $that;
    }
    public static function delete(string $route, string | Closure $controller)
    {
        $that = static::getInstance();

        $that->routes["DELETE"][$route] = ['controller' => $controller];
        $that->recentlyAddedRoute = ['method' => "DELETE", "route" => $route, 'controller' => $controller];

        return $that;
    }

    public function getModels()
    {
        $that = static::getInstance();
        return $that->models;
    }
    public static function flush()
    {
        $that = static::getInstance();
        $that->routes = [];
        $that->models = [];
        $that->matchedRoute = "";
    }

    function addORMRoute(string $method, string $route, string $model, string $action)
    {
        $that = static::getInstance();

        $that->ormRoutes[$method][$route] = [
            'model' => $model,
            'action' => $action
        ];
    }

    public static function dispatch()
    {
        try {
            $that = static::getInstance();

            $uri = $_SERVER['REQUEST_URI'];
            $method = $_SERVER['REQUEST_METHOD'];
            if (isset($that->routes[$method])) {
                foreach ($that->routes[$method] as $route => $info) {
                    if (count($that->matchedRoute($route, $uri))) {


                        $that->lookForModel($route);
                        $that->applyMiddleware($info);

                        if (is_callable($info['controller'])) {
                            $info['controller']();
                            return;
                        }

                        $that->callController($info['controller']);
                        return;
                    }
                }
            }


            // Handle 404 Not Found
            echo '404 Not Found';
        } catch (\Throwable $th) {
            if (env("APP_DEBUG")) {
                return core_view("server-error", ['title' => $th->getFile(), 'message' => $th->getMessage()]);
            } else {
                throw new Exception("Controller Error! " . $th->getMessage());
            }
        }
    }

    function matchedRoute(string $route, string $uri)
    {
        $that = self::getInstance();

        $pattern = '#^' . preg_replace('/{([^\/]+)}/', '([^/]+)', $route) . '$#';
        $matches = [];
        preg_match($pattern, $uri, $matches);

        if (!empty($matches))
            $that->matchedRoute = $route;

        return $matches;
    }

    function lookForModel(string $route)
    {
        $that = static::getInstance();

        $pattern = '/\{(.*?)\}/';
        $matches = [];

        preg_match_all($pattern, $route, $matches);

        foreach ($matches[1] as $modelName) {
            $modelName = Str::convertSnakeToTitleCase($modelName);

            $modelClass = 'App\\Models\\' . $modelName;

            if (class_exists($modelClass)) {
                $that->models[] = $modelClass;
            }
            // else {
            //     $that->models[] = null;
            // }
        }
    }
    function applyMiddleware(array $route)
    {
        foreach ($route['middlewares'] as $middleware) {
            Middleware::setup($middleware)->apply();
        }
    }
    function callController(string $controller)
    {
        $that = static::getInstance();

        list($controllerName, $methodName) = explode('@', $controller);
        $controllerName = 'App\\Controllers\\' . $controllerName;

        if (class_exists($controllerName)) {
            $controllerInstance = new $controllerName();

            if (method_exists($controllerInstance, $methodName)) {
                $params = $that->getRouteParams($controller);

                call_user_func_array([$controllerInstance, $methodName], $params);
                return;
            }
        }

        // Handle 404 Not Found
        echo '404 Not Found';
    }

    function callORMAction(string $model, string $action)
    {
        $that = self::getInstance();

        $modelName = 'App\\Models\\' . $model;

        if (class_exists($modelName)) {
            $modelInstance = new $modelName();

            if (method_exists($modelInstance, $action)) {
                $params = $that->getRouteParams($model);
                call_user_func_array([$modelInstance, $action], $params);
                return;
            }
        }

        // Handle 404 Not Found
        echo '404 Not Found';
    }

    function getRouteParams(string $controllerOrModel)
    {
        $that = self::getInstance();

        $params = [Request::getInstance()];
        $uriSegments = explode('/', $_SERVER['REQUEST_URI']);
        $uriSegments = array_filter($uriSegments);

        $routeSegments = explode('/', $that->matchedRoute);
        $routeSegments = array_filter($routeSegments);
        // dd($that->models);
        foreach ($routeSegments as $index => $segment) {
            if (strpos($segment, '{') === 0 && strpos($segment, '}') === strlen($segment) - 1) {
                $paramName = trim($segment, '{}');
                $paramValue = isset($uriSegments[$index]) ? $uriSegments[$index] : null;
                $params[$paramName] = $paramValue;
                $modelName = Str::convertSnakeToTitleCase($paramName);
                $modelClass = 'App\\Models\\' .  $modelName;
                if (class_exists($modelClass)) {
                    $modelInstance = new $modelClass();
                    $modelValue = $modelInstance->where("id", $uriSegments[$index])->first();
                    if ($modelValue) {
                        $params[$paramName] =  $modelValue;
                    } else {
                        throw new Exception("No database recored for <strong>\"" . $uriSegments[$index] . "\"</strong> in " . Str::plural($paramName));
                    }
                }
            }
        }

        return $params;
    }
}
