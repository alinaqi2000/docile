<?php

namespace Docile\Core;

use Docile\Docile;
use Exception;

class Middleware extends Docile
{
    public $middleware;
    public static function setup(array $middleware)
    {
        $that = static::getInstance();
        $that->middleware = $middleware;
        return $that;
    }
    private function locate()
    {
        $that = static::getInstance();
        $middlewareName = 'App\\Http\Middlewares\\' . ucwords($that->middleware['name']) . "Middleware";
        if (class_exists($middlewareName)) {
            return new $middlewareName();
        }
        return;
    }
    public static function apply()
    {

        try {
            $that = static::getInstance();
            if ($middlewareInstance = $that->locate()) {
                if (method_exists($middlewareInstance, 'intercept')) {
                    $validateInterception = call_user_func_array([$middlewareInstance, 'intercept'], []);
                    if (!$validateInterception) {
                        if ($that->middleware['redirect']) {
                            return  $that->redirect($that->middleware['redirect']);
                        }
                        if (env("APP_DEBUG")) {
                            return Error::destroy("Route is protected.", "middleware");
                        } else {
                            throw new Exception("Route is protected.");
                        }
                    }
                    return;
                }
            }
        } catch (\Throwable $th) {
            if (env("APP_DEBUG")) {
                return core_view("server-error", ['title' => $th->getFile(), 'message' => $th->getMessage()]);
            }
            die("Unauthorized!");
        }
    }
    private function redirect($uri)
    {
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === FALSE ? 'http' : 'https';

        header("Location: " . $protocol . '://' . $_SERVER['HTTP_HOST'] . $uri);

        exit();
    }
}
