<?php
/**
 * \TechDivision\WebServer\ServerJsonConfiguration
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
 * Class ServerJsonConfiguration
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/TechDivision_WebServer
 */
class ServerJsonConfiguration implements ServerConfigurationInterface
{

    /**
     * Hold's raw data instance
     *
     * @var \stdClass
     */
    protected $data;

    /**
     * Hold's the handlers array
     *
     * @var array
     */
    protected $handlers;

    /**
     * Hold's the virtual hosts array
     *
     * @var array
     */
    protected $virtualHosts;

    /**
     * Hold's the authentications array
     *
     * @var array
     */
    protected $authentications;

    /**
     * Hold's the rewrites array
     *
     * @var array
     */
    protected $rewrites = array();

    /**
     * Holds the environmentVariables array
     *
     * @var array
     */
    protected $environmentVariables = array();

    /**
     * Constructs config
     *
     * @param \stdClass $data The data object use
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Return's type
     *
     * @return string
     */
    public function getType()
    {
        return $this->data->type;
    }

    /**
     * Return's transport
     *
     * @return string
     */
    public function getTransport()
    {
        return $this->data->transport;
    }

    /**
     * Return's address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->data->address;
    }

    /**
     * Return's port
     *
     * @return int
     */
    public function getPort()
    {
        return (int)$this->data->port;
    }

    /**
     * Return's software
     *
     * @return string
     */
    public function getSoftware()
    {
        return $this->data->software;
    }

    /**
     * Return's admin
     *
     * @return string
     */
    public function getAdmin()
    {
        return $this->data->admin;
    }

    /**
     * Return's keep-alive max connection
     *
     * @return int
     */
    public function getKeepAliveMax()
    {
        return (int)$this->data->keepAliveMax;
    }

    /**
     * Return's keep-alive timeout
     *
     * @return int
     */
    public function getKeepAliveTimeout()
    {
        return (int)$this->data->keepAliveTimeout;
    }

    /**
     * Return's admin
     *
     * @return string
     */
    public function getErrorsPageTemplatePath()
    {
        return $this->data->errorsPageTemplatePath;
    }

    /**
     * Return's worker number
     *
     * @return int
     */
    public function getWorkerNumber()
    {
        return (int)$this->data->workerNumber;
    }

    /**
     * Return's context type
     *
     * @return string
     */
    public function getServerContextType()
    {
        return $this->data->serverContext;
    }

    /**
     * Return's socket type
     *
     * @return string
     */
    public function getSocketType()
    {
        return $this->data->socket;
    }

    /**
     * Return's worker type
     *
     * @return string
     */
    public function getWorkerType()
    {
        return $this->data->worker;
    }

    /**
     * Return's document root
     *
     * @return string
     */
    public function getDocumentRoot()
    {
        return $this->data->documentRoot;
    }

    /**
     * Return's connection handlers
     *
     * @return array
     */
    public function getConnectionHandlers()
    {
        return $this->data->connectionHandlers;
    }

    /**
     * Return's the virtual hosts
     *
     * @return array
     */
    public function getVirtualHosts()
    {
        if (!$this->virtualHosts) {
            foreach ($this->data->virtualHosts as $virtualHost) {
                $virtualHostNames = explode(' ', $virtualHost->name);
                // get all params
                $params = get_object_vars($virtualHost);
                // remove name
                unset($params["name"]);

                // If we got rewrites we have to preserve them and remove them from the params array
                $rewrites = array();
                if (isset($params['rewrites'])) {

                    foreach ($params['rewrites'] as $rewrite) {

                        $rewrites[] = (array)$rewrite;
                    }

                    unset($params['rewrites']);
                }

                // If we got environment variables we have to preserve them and remove them from the params array
                $environmentVariables = array();
                if (isset($params['environmentVariables'])) {

                    foreach ($params['environmentVariables'] as $environmentVariable) {

                        $environmentVariables[] = (array)$environmentVariable;
                    }

                    unset($params['environmentVariables']);
                }

                // set all virtual host information's
                foreach ($virtualHostNames as $virtualHostName) {
                    // set all virtual hosts params per key for faster matching later on
                    $this->virtualHosts[trim($virtualHostName)]['params'] = $params;
                    // Also set all the rewrites for this virtual host
                    $this->virtualHosts[trim($virtualHostName)]['rewrites'] = $rewrites;
                    // Also add the environmentVariables to the virtual host configuration
                    $this->virtualHosts[trim($virtualHostName)]['environmentVariables'] = $environmentVariables;
                }
            }
        }

        return $this->virtualHosts;
    }

    /**
     * Return's the authentications
     *
     * @return array
     */
    public function getAuthentications()
    {
        if (!$this->authentications) {
            foreach ($this->data->authentications as $authentication) {
                $authenticationType = $authentication->uri;
                // get all params
                $params = get_object_vars($authentication);
                // remove type
                unset($params["uri"]);
                // set all authentication information's
                $this->authentications[$authenticationType] = $params;
            }
        }

        return $this->authentications;
    }

    /**
     * Return's modules
     *
     * @return array
     */
    public function getModules()
    {
        return $this->data->modules;
    }

    /**
     * Return's handlers
     *
     * @return array
     */
    public function getHandlers()
    {
        if (!$this->handlers) {
            foreach ($this->data->handlers as $handler) {
                $this->handlers[$handler->extension] = $handler->name;
            }
        }

        return $this->handlers;
    }

    /**
     * Return's cert path
     *
     * @return string
     */
    public function getCertPath()
    {
        return $this->data->certPath;
    }

    /**
     * Return's passphrase
     *
     * @return string
     */
    public function getPassphrase()
    {
        return $this->data->passphrase;
    }

    /**
     * Returns the rewrite configuration.
     *
     * @return array
     */
    public function getRewrites()
    {
        // init rewrites
        if (!$this->rewrites) {

            $this->rewrites = array();
        }

        // prepare the array with the rewrite rules
        foreach ($this->data->rewrites as $rewrite) {

            // Build up the array entry
            $this->rewrites[] = array(
                'condition' => $rewrite->condition,
                'target' => $rewrite->target,
                'flag' => $rewrite->flag
            );
        }

        // return the rewrites
        return $this->rewrites;
    }

    /**
     * Returns the environment variable configuration
     *
     * @return array
     */
    public function getEnvironmentVariables()
    {
        // init EnvironmentVariables
        if (!$this->environmentVariables) {

            // prepare the array with the environment variables
            foreach ($this->data->environmentVariables as $environmentVariable) {

                // Build up the array entry
                $this->environmentVariables[] = array(
                    'condition' => $environmentVariable->condition,
                    'definition' => $environmentVariable->definition
                );
            }
        }

        // return the environmentVariables
        return $this->environmentVariables;
    }

    /**
     * Return's the authentications
     *
     * @return array
     */
    public function getAccesses()
    {
        if (!$this->accesses) {
            foreach ($this->data->accesses as $access) {
                $accessType = $access->type;
                // get all params
                $params = get_object_vars($access);
                // remove type
                unset($params["type"]);
                // set all accesses information's
                $this->accesses[$accessType][] = $params;
            }
        }
        return $this->accesses;
    }
}
