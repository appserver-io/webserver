<?php

namespace TechDivision\WebServer;

use TechDivision\WebServer\Interfaces\ServerInterface;
use TechDivision\WebServer\Interfaces\ConfigInterface;

class Server implements ServerInterface
{

    protected $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function run()
    {
        // init config var for shorter calls
        $config = $this->getConfig();

        $streamSocketClassName = $config->getSocketClassName();
        $connectionClassName = $config->getConnectionClassName();
        $parserClassName = $config->getParserClassName();
        $requestClassName= $config->getRequestClassName();
        $responseClassName= $config->getResponseClassName();

        // create server stream connection
        $streamSocketServer = $streamSocketClassName::getServerInstance(
            $config->getServerListen() . ':' . $config->getServerPort()
        );

        // setup parser
        $httpParser = new $parserClassName(
            new $requestClassName(),
            new $responseClassName('/home/zelgerj/Repositories/TechDivision_WebServer/src/var/www/index.html')
        );

        // accept http connections
        while($httpConnection = new $connectionClassName($streamSocketServer->accept(), $httpParser)) {
            $httpConnection->negotiate();
        }
    }

}