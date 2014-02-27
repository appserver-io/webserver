<?php
/**
 * \TechDivision\WebServer\JsonConfig
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

use TechDivision\WebServer\Interfaces\ConfigInterface;

/**
 * Class JsonConfig
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
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

    public function getWorkerNumber()
    {
        return $this->data->server->workerNumber;
    }


}
