<?php
/**
 * \TechDivision\WebServer\ServerXmlConfiguration
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/TechDivision_WebServer
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
 * @link      https://github.com/techdivision/TechDivision_WebServer
 */
class ServerXmlConfiguration implements ServerConfigurationInterface
{

    /**
     * Constructs config
     *
     * @param \SimpleXMLElement $node The simple xml element used to build config
     */
    public function __construct($node)
    {
        $this->type = (string)$node->attributes()->type;
        $this->workerType = (string)$node->attributes()->worker;
        $this->socketType = (string)$node->attributes()->socket;
        $this->serverContextType = (string)$node->attributes()->serverContext;
        $this->transport = (string)array_shift($node->xpath(".//param[@name='transport']"));
        $this->address = (string)array_shift($node->xpath(".//param[@name='address']"));
        $this->port = (int)array_shift($node->xpath(".//param[@name='port']"));
        $this->software = (string)array_shift($node->xpath(".//param[@name='software']"));
        $this->workerNumber = (int)array_shift($node->xpath(".//param[@name='workerNumber']"));
        $this->certPath = (string)array_shift($node->xpath(".//param[@name='certPath']"));
        $this->passphrase = (string)array_shift($node->xpath(".//param[@name='passphrase']"));
        $this->documentRoot = (string)array_shift($node->xpath(".//param[@name='documentRoot']"));
        $this->admin = (string)array_shift($node->xpath(".//param[@name='admin']"));

        // init modules
        foreach ($node->modules->module as $moduleNode) {
            $this->modules[] = (string)$moduleNode->attributes()->type;
        }
        // init connection handlers
        foreach ($node->connectionHandlers->connectionHandler as $connectionHandlerNode) {
            $this->connectionHandlers[] = (string)$connectionHandlerNode->attributes()->type;
        }
        // init handlers
        foreach ($node->handlers->handler as $handlerNode) {
            $this->handlers[(string)$handlerNode->attributes()->extension] = (string)$handlerNode->attributes()->name;
        }
        // init virutalHosts
        foreach ($node->virtualHosts->virtualHost as $virtualHostNode) {
            $virtualHostNames = explode(' ' , (string)$virtualHostNode->attributes()->name);
            $params = array();
            foreach ($virtualHostNode->params->param as $paramNode) {
                $paramName = (string)$paramNode->attributes()->name;
                $params[$paramName] = (string)array_shift($virtualHostNode->xpath(".//param[@name='$paramName']"));
            }
            foreach ($virtualHostNames as $virtualHostName) {
                // set all virtual hosts params per key for faster matching later on
                $this->virtualHosts[trim($virtualHostName)] = $params;
            }
        }
    }

    /**
     * Return's type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return's transport
     *
     * @return string
     */
    public function getTransport()
    {
        $t = $this->transport;
        return $this->transport;
    }

    /**
     * Return's address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Return's port
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Return's software
     *
     * @return string
     */
    public function getSoftware()
    {
        return $this->software;
    }

    /**
     * Return's admin
     *
     * @return string
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Return's worker number
     *
     * @return int
     */
    public function getWorkerNumber()
    {
        return $this->workerNumber;
    }

    /**
     * Return's server context type
     *
     * @return string
     */
    public function getServerContextType()
    {
        return $this->serverContextType;
    }

    /**
     * Return's socket type
     *
     * @return string
     */
    public function getSocketType()
    {
        return $this->socketType;
    }

    /**
     * Return's worker type
     *
     * @return string
     */
    public function getWorkerType()
    {
        return $this->workerType;
    }

    /**
     * Return's document root
     *
     * @return string
     */
    public function getDocumentRoot()
    {
        return $this->documentRoot;
    }

    /**
     * Return's the connection handlers
     *
     * @return array
     */
    public function getConnectionHandlers()
    {
        return $this->connectionHandlers;
    }

    /**
     * Return's the virtual hosts
     *
     * @return array
     */
    public function getVirtualHosts()
    {
        return $this->virtualHosts;
    }

    /**
     * Return's modules
     *
     * @return array
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * Return's array
     *
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Return's cert path
     *
     * @return string
     */
    public function getCertPath()
    {
        return $this->certPath;
    }

    /**
     * Return's passphrase
     *
     * @return string
     */
    public function getPassphrase()
    {
        return $this->passphrase;
    }
}
