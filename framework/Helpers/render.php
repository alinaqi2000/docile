<?php

use Docile\Http\Response;
use Docile\Http\Session;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

if (!function_exists("view")) {
    function view($viewName, $data = [])
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../app/Views/');
        $twig = new Environment($loader);
        $twig->addFunction(new TwigFunction('session', fn ($key) => Session::get($key)));



        echo $twig->render($viewName . '.twig', $data);
        die();
    }
}
if (!function_exists("core_view")) {
    function core_view($viewName, $data = [])
    {
        $viewPath = __DIR__ . '/../../framework/Views/' . $viewName . '.php';
        // dd($viewPath);
        if (file_exists($viewPath)) {
            ob_start();

            extract($data);

            include $viewPath;

            echo ob_get_clean();
            die();
        }

        throw new Exception('View not found: ' . $viewName);
    }
}
if (!function_exists("response")) {
    function response($data = [], $status = 200, $headers = [])
    {

        $response = Response::make($data, $status, $headers);
        if (empty($data))
            return $response;
        return $response->send();
    }
}
