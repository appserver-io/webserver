<?php

namespace TechDivision\WebServer;

use TechDivision\WebServer\Interfaces\ConfigInterface;

class ListenerThread extends \Thread
{

    public $connectionResource;

    public function __construct($socketClassName, $connectionResource, ConfigInterface $config)
    {
        $this->socketClassName = $socketClassName;
        $this->connectionResource = $connectionResource;
        $this->config = $config;
    }

    public function run()
    {
        require WEBSERVER_BASEDIR . '../vendor/autoload.php';

        $config = $this->config;
        $socketClassName = $this->socketClassName;
        $socketServer = $socketClassName::getInstance($this->connectionResource);

        $connectionClassName = $config->getConnectionClassName();
        $parserClassName = $config->getParserClassName();
        $requestClassName= $config->getRequestClassName();
        $responseClassName= $config->getResponseClassName();

        // setup parser
        $parser = new $parserClassName(new $requestClassName(), new $responseClassName());

        // accept http connections
        while($httpConnection = new $connectionClassName($socketServer->accept(), $parser)) {
            $httpConnection->negotiate();
        }

    }

}
