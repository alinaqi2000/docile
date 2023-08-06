<?php


namespace Docile\Tests\Unit;

use Docile\Core\Router;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{

    public function testRoutesForValidation(): void
    {
        $routes = [
            ['route' => "/", "uri" => "/"],
            ['route' => "/about", "uri" => "/about"],
        ];
        $router = Router::getInstance();
        foreach ($routes as $route) {
            Router::get($route['route'], 'HomeController@index');
            $this->assertSame(1, count($router->matchedRoute($route['route'], $route['uri'])));
        }
    }
    public function testRoutesForModels(): void
    {
        $routes = [
            ['route' => "/users/{user}", "models" => ["App\Models\User"]],
            ['route' => "/users/{user}/edit/{car}", "models" => ["App\Models\User"]],
        ];
        $router = Router::getInstance();
        foreach ($routes as $route) {
            Router::get($route['route'], 'HomeController@index');
            $router->lookForModel($route['route']);
            $this->assertSame($route['models'], $router->getModels());
            Router::flush();
        }
    }
}
