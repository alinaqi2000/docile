<?php

namespace Docile\Http;

use Docile\Docile;

class Response extends Docile
{
    public static function make($data = [], $status = 200, $headers = [])
    {
        $response = self::getInstance();
        $response->setData($data);
        $response->setStatusCode($status);
        $response->setHeaders($headers);

        return $response;
    }
    public static function json($data = [], $status = 200, $headers = [])
    {
        $response = self::getInstance();

        if (empty($response->data))
            $response->setData(json_encode($data));
        else
            $response->setData(json_encode($response->data));

        $response->setStatusCode($status);
        $response->setHeaders($headers);

        $response->send();
    }
    public function setData($data)
    {
        $response = self::getInstance();
        $response->data = $data;
    }

    public function setStatusCode($status)
    {
        $response = self::getInstance();
        $response->status = $status;
    }

    public function setHeaders($headers)
    {
        $response = self::getInstance();
        $response->headers = $headers;
    }

    public function send()
    {
        $response = self::getInstance();

        http_response_code($response->status);

        foreach ($response->headers as $header => $value) {
            header($header . ': ' . $value);
        }

        echo $response->data;
        exit;
    }
}
