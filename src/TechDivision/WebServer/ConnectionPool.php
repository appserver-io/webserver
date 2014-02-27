<?php
/**
 * \TechDivision\WebServer\ConnectionPool
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

use TechDivision\WebServer\Interfaces\PoolInterface;
use TechDivision\WebServer\Sockets\SocketInterface;

/**
 * Class ConnectionPool
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class ConnectionPool extends \Stackable implements PoolInterface
{
    /**
     * Implement run method due to c-class wrapper abstract specifications
     *
     * @return void
     */
    public function run()
    {
        // do nothing
    }

    /**
     * Sets the socket classname to instantiate for this connection pool
     *
     * @param $socketClassName
     */
    public function __construct($socketClassName)
    {
        $this->socketClassName = $socketClassName;
    }

    /**
     * Return's the socket class name to instance for connections
     *
     * @return string
     */
    public function getSocketClassName()
    {
        return $this->socketClassName;
    }

    /**
     * Return's a connection instance bundled with socket resource
     *
     * @param resource $connectionResource The resource to set into the connection instance
     *
     * @return \TechDivision\WebServer\Sockets\SocketInterface The Connection
     */
    protected function getConnectionInstance($connectionResource)
    {
        $socketClassName = $this->socketClassName;
        return $socketClassName::getInstance($connectionResource);
    }

    /**
     * Return's a connection instance bundled with resource reference by id
     *
     * @param int $connectionResourceId The connection resource id to bundle the connection instance with
     *
     * @return \TechDivision\WebServer\Sockets\SocketInterface The ready to use connection instance
     */
    public function get($connectionResourceId)
    {
        return $this->getConnectionInstance($this["res#$connectionResourceId"]);
    }

    /**
     * Add's a connection instance to pool
     *
     * @param \TechDivision\WebServer\Sockets\SocketInterface $connection The connection instance
     *
     * @return int The connection resource id for further usage
     */
    public function add(SocketInterface $connection)
    {
        $connectionId = $connection->getConnectionResourceId();
        $this["res#$connectionId"] = $connection->getConnectionResource();
        return $connectionId;
    }
}