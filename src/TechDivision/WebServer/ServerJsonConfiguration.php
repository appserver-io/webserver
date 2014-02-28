<?php
/**
 * \TechDivision\WebServer\ServerJsonConfiguration
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
 * Class ServerJsonConfiguration
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class ServerJsonConfiguration implements ServerConfigurationInterface
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getType()
    {
        return $this->data->type;
    }

    public function getTransport()
    {
        return $this->data->transport;
    }

    public function getAddress()
    {
        return $this->data->address;
    }

    public function getPort()
    {
        return $this->data->port;
    }

    public function getSignature()
    {
        return $this->data->signature;
    }

    public function getWorkerNumber()
    {
        return $this->data->workerNumber;
    }

    public function getServerContextType()
    {
        return $this->data->serverContext;
    }

    public function getSocketType()
    {
        return $this->data->socket;
    }

    public function getWorkerType()
    {
        return $this->data->worker;
    }

    public function getDocumentRoot()
    {
        return $this->data->documentRoot;
    }

    public function getConnectionHandlers()
    {
        return $this->data->connectionHandlers;
    }

    public function getModules()
    {
        return $this->data->modules;
    }

    public function getHandlers()
    {
        return $this->data->handlers;
    }

    public function getCertPath()
    {
        return $this->data->certPath;
    }

    public function getPassphrase()
    {
        return $this->data->passphrase;
    }


}
