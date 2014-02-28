<?php
/**
 * \TechDivision\WebServer\ServerXmlConfiguration
 *
 * PHP version 5
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace TechDivision\WebServer;

use TechDivision\WebServer\Interfaces\ServerConfigurationInterface;

/**
 * Class ServerXmlConfiguration
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class ServerXmlConfiguration implements ServerConfigurationInterface
{

    public function __construct($node)
    {
        $this->type = (string)$node->attributes()->type;
        $this->workerType = (string)$node->attributes()->worker;
        $this->socketType = (string)$node->attributes()->socket;
        $this->serverContextType = (string)$node->attributes()->serverContext;

        $this->transport = (string)array_shift($node->xpath(".//param[@name='transport']"));
        $this->address = (string)array_shift($node->xpath(".//param[@name='address']"));
        $this->port = (int)array_shift($node->xpath(".//param[@name='port']"));
        $this->signature = (string)array_shift($node->xpath(".//param[@name='signature']"));
        $this->workerNumber = (int)array_shift($node->xpath(".//param[@name='workerNumber']"));
        $this->certPath = (string)array_shift($node->xpath(".//param[@name='certPath']"));
        $this->passphrase = (string)array_shift($node->xpath(".//param[@name='passphrase']"));
        $this->documentRoot = (string)array_shift($node->xpath(".//param[@name='documentRoot']"));

        foreach ($node->modules->module as $moduleNode) {
            $this->modules[] = (string)$moduleNode->attributes()->type;
        }

        foreach ($node->connectionHandlers->connectionHandler as $connectionHandlerNode) {
            $this->connectionHandlers[] = (string)$connectionHandlerNode->attributes()->type;
        }

        foreach ($node->handlers->handler as $handlerNode) {
            $handler = new \stdClass();
            $handler->name = (string)$handlerNode->attributes()->extension;
            $handler->extension = (string)$handlerNode->attributes()->name;
            $this->handlers[] = $handler;
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function getTransport()
    {
        $t = $this->transport;
        return $this->transport;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getSignature()
    {
        return $this->signature;
    }

    public function getWorkerNumber()
    {
        return $this->workerNumber;
    }

    public function getServerContextType()
    {
        return $this->serverContextType;
    }

    public function getSocketType()
    {
        return $this->socketType;
    }

    public function getWorkerType()
    {
        return $this->workerType;
    }

    public function getDocumentRoot()
    {
        return $this->documentRoot;
    }

    public function getConnectionHandlers()
    {
        return $this->connectionHandlers;
    }

    public function getModules()
    {
        return $this->modules;
    }

    public function getHandlers()
    {
        return $this->handlers;
    }

    public function getCertPath()
    {
        return $this->certPath;
    }

    public function getPassphrase()
    {
        return $this->passphrase;
    }

}
