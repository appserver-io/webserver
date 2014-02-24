<?php

namespace TechDivision\WebServer;

use TechDivision\WebServer\Interfaces\ConfigInterface;

class JsonConfig implements ConfigInterface
{
    protected $data;

    public function __construct($filename)
    {
        $this->data = json_decode(file_get_contents($filename));
    }

    public function getServerListen()
    {
        return $this->data->server->listen;
    }

    public function getServerPort()
    {
        return $this->data->server->port;
    }

    public function getSocketClassName()
    {
        return $this->data->classes->socket;
    }

    public function getParserClassName()
    {
        return $this->data->classes->parser;
    }

    public function getConnectionClassName()
    {
        return $this->data->classes->connection;
    }

    public function getRequestClassName()
    {
        return $this->data->classes->request;
    }

    public function getResponseClassName()
    {
        return $this->data->classes->response;
    }

}
