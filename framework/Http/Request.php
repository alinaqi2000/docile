<?php

namespace Docile\Http;

use Docile\Docile;

class Request extends Docile
{
    private $input;

    public function __construct()
    {
        $this->input = array_merge($_GET, $_POST);
    }

    public function all()
    {
        return $this->input;
    }

    public function get($key, $default = null)
    {
        return $this->input[$key] ?? $default;
    }

    public function has($key)
    {
        return isset($this->input[$key]);
    }

    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function url()
    {
        return $_SERVER['REQUEST_URI'];
    }

    // Additional methods can be added as per your specific requirements
}
