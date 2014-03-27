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
     * The configured rewrite rules
     *
     * @var array $rewrites
     */
    protected $rewrites;

    /**
     * Holds the environmentVariables array
     *
     * @var array
     */
    protected $environmentVariables = array();

    /**
     * Constructs config
     *
     * @param \SimpleXMLElement $node The simple xml element used to build config
     */
    public function __construct($node)
    {
        // prepare properties
        $this->type = (string)$node->attributes()->type;
        $this->workerType = (string)$node->attributes()->worker;
        $this->socketType = (string)$node->attributes()->socket;
        $this->serverContextType = (string)$node->attributes()->serverContext;
        $this->transport = (string)array_shift($node->xpath("./params/param[@name='transport']"));
        $this->address = (string)array_shift($node->xpath("./params/param[@name='address']"));
        $this->port = (int)array_shift($node->xpath("./params/param[@name='port']"));
        $this->software = (string)array_shift($node->xpath("./params/param[@name='software']"));
        $this->workerNumber = (int)array_shift($node->xpath("./params/param[@name='workerNumber']"));
        $this->certPath = (string)array_shift($node->xpath("./params/param[@name='certPath']"));
        $this->passphrase = (string)array_shift($node->xpath("./params/param[@name='passphrase']"));
        $this->documentRoot = (string)array_shift($node->xpath("./params/param[@name='documentRoot']"));
        $this->admin = (string)array_shift($node->xpath("./params/param[@name='admin']"));
        $this->keepAliveMax = (string)array_shift($node->xpath("./params/param[@name='keepAliveMax']"));
        $this->keepAliveTimeout = (string)array_shift($node->xpath("./params/param[@name='keepAliveTimeout']"));
        $this->errorsPageTemplatePath = (string)array_shift($node->xpath("./params/param[@name='errorsPageTemplatePath']"));

        // prepare modules
        $this->modules = $this->prepareModules($node);
        // prepare connection handlers
        $this->connectionHandlers = $this->prepareConnectionHandlers($node);
        // prepare handlers
        $this->handlers = $this->prepareHandlers($node);
        // prepare virutalHosts
        $this->virtualHosts = $this->prepareVirtualHosts($node);
        // prepare rewrites
        $this->rewrites = $this->prepareRewrites($node);
        // prepare environmentVariables
        $this->environmentVariables = $this->prepareEnvironmentVariables($node);
        // prepare authentications
        $this->authentications = $this->prepareAuthentications($node);
        // prepare accesses
        $this->accesses = $this->prepareAccesses($node);
    }

    /**
     * Prepares the modules array based on a simple xml elemend node
     *
     * @param \SimpleXMLElement $node The xml node
     *
     * @return array
     */
    public function prepareModules(\SimpleXMLElement $node)
    {
        $modules = array();
        if ($node->modules) {
            foreach ($node->modules->module as $moduleNode) {
                $modules[] = (string)$moduleNode->attributes()->type;
            }
        }
        return $modules;
    }

    /**
     * Prepares the connectionHandlers array based on a simple xml elemend node
     *
     * @param \SimpleXMLElement $node The xml node
     *
     * @return array
     */
    public function prepareConnectionHandlers(\SimpleXMLElement $node)
    {
        $connectionHandlers = array();
        if ($node->connectionHandlers) {
            foreach ($node->connectionHandlers->connectionHandler as $connectionHandlerNode) {
                $connectionHandlerType = (string)$connectionHandlerNode->attributes()->type;
                $connectionHandlers[] = $connectionHandlerType;
            }
        }
        return $connectionHandlers;
    }

    /**
     * Prepares the handlers array based on a simple xml elemend node
     *
     * @param \SimpleXMLElement $node The xml node
     *
     * @return array
     */
    public function prepareHandlers(\SimpleXMLElement $node)
    {
        $handlers = array();
        if ($node->handlers) {
            foreach ($node->handlers->handler as $handlerNode) {
                $handlers[(string)$handlerNode->attributes()->extension] = (string)$handlerNode->attributes()->name;
            }
        }
        return $handlers;
    }

    /**
     * Prepares the virtual hosts array based on a simple xml elemend node
     *
     * @param \SimpleXMLElement $node The xml node
     *
     * @return array
     */
    public function prepareVirtualHosts(\SimpleXMLElement $node)
    {
        $virutalHosts = array();
        if ($node->virtualHosts) {
            foreach ($node->virtualHosts->virtualHost as $virtualHostNode) {
                $virtualHostNames = explode(' ', (string)$virtualHostNode->attributes()->name);
                $params = array();
                foreach ($virtualHostNode->params->param as $paramNode) {
                    $paramName = (string)$paramNode->attributes()->name;
                    $params[$paramName] = (string)array_shift($virtualHostNode->xpath(".//param[@name='$paramName']"));
                }
                foreach ($virtualHostNames as $virtualHostName) {
                    // set all virtual hosts params per key for faster matching later on
                    $virutalHosts[trim($virtualHostName)] = array(
                        'params' => $params,
                        'rewrites' => $this->prepareRewrites($virtualHostNode),
                        'environmentVariables' => $this->prepareEnvironmentVariables($virtualHostNode),
                        'authentications' => $this->prepareAuthentications($virtualHostNode),
                        'accesses' => $this->prepareAccesses($virtualHostNode)
                    );
                }
            }
        }
        return $virutalHosts;
    }

    /**
     * Prepares the rewrites array based on a simple xml elemend node
     *
     * @param \SimpleXMLElement $node The xml node
     *
     * @return array
     */
    public function prepareRewrites(\SimpleXMLElement $node)
    {
        $rewrites = array();
        if ($node->rewrites) {
            foreach ($node->rewrites->rewrite as $rewriteNode) {
                // Cut of the SimpleXML attributes wrapper and attach it to our rewrites
                $rewrite = (array)$rewriteNode;
                $rewrites[] = array_shift($rewrite);
            }
        }
        return $rewrites;
    }

    /**
     * Prepares the environmentVariables array based on a simple xml elemend node
     *
     * @param \SimpleXMLElement $node The xml node
     *
     * @return array
     */
    public function prepareEnvironmentVariables(\SimpleXMLElement $node)
    {
        $environmentVariables = array();
        if ($node->environmentVariables) {
            foreach ($node->environmentVariables->environmentVariable as $environmentVariableNode) {

                // Cut of the SimpleXML attributes wrapper and attach it to our environment variable
                $environmentVariable = (array)$environmentVariableNode;
                $environmentVariables[] = array_shift($environmentVariable);
            }
        }
        return $environmentVariables;
    }

    /**
     * Prepares the authentications array based on a simple xml elemend node
     *
     * @param \SimpleXMLElement $node The xml node
     *
     * @return array
     */
    public function prepareAuthentications(\SimpleXMLElement $node)
    {
        $authentications = array();
        if ($node->authentications) {
            foreach ($node->authentications->authentication as $authenticationNode) {
                $params = array();
                foreach ($authenticationNode->params->param as $paramNode) {
                    $paramName = (string)$paramNode->attributes()->name;
                    $params[$paramName] = (string)array_shift($authenticationNode->xpath(".//param[@name='$paramName']"));
                }
                $authentications[(string)$authenticationNode->attributes()->uri] = $params;
            }
        }
        return $authentications;
    }

    /**
     * Prepares the access array based on a simple xml elemend node
     *
     * @param \SimpleXMLElement $node The xml node
     *
     * @return array
     */
    public function prepareAccesses(\SimpleXMLElement $node)
    {
        // init accesses
        $accesses = array();
        if ($node->accesses) {
            foreach ($node->accesses->access as $accessNode) {
                $params = array();
                foreach ($accessNode->params->param as $paramNode) {
                    $paramName = (string)$paramNode->attributes()->name;
                    $params[$paramName] = (string)array_shift($accessNode->xpath(".//param[@name='$paramName']"));
                }
                $accesses[(string)$accessNode->attributes()->type][] = $params;
            }
        }
        return $accesses;
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
        return $this->transport;
    }

    /**
     * Returns rewrites
     *
     * @return array
     */
    public function getRewrites()
    {
        return $this->rewrites;
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
        return (int)$this->port;
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
     * Return's keep-alive max connection
     *
     * @return int
     */
    public function getKeepAliveMax()
    {
        return (int)$this->keepAliveMax;
    }

    /**
     * Return's keep-alive timeout
     *
     * @return int
     */
    public function getKeepAliveTimeout()
    {
        return (int)$this->keepAliveTimeout;
    }

    /**
     * Return's template path for errors page
     *
     * @return string
     */
    public function getErrorsPageTemplatePath()
    {
        return $this->errorsPageTemplatePath;
    }

    /**
     * Return's worker number
     *
     * @return int
     */
    public function getWorkerNumber()
    {
        return (int)$this->workerNumber;
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
     * Return's the authentication information's
     *
     * @return array
     */
    public function getAuthentications()
    {
        return $this->authentications;
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

    /**
     * Returns the environment variable configuration
     *
     * @return array
     */
    public function getEnvironmentVariables()
    {
        // return the environmentVariables
        return $this->environmentVariables;
    }

    /**
     * Returns the access configuration.
     *
     * @return array
     */
    public function getAccesses()
    {
        return $this->accesses;
    }
}
