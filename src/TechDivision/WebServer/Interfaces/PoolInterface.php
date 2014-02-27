<?php
/**
 * \TechDivision\WebServer\Interfaces\PoolInterface
 *
 * PHP version 5
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace TechDivision\WebServer\Interfaces;
use TechDivision\WebServer\Sockets\SocketInterface;

/**
 * Interface PoolInterface
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
interface PoolInterface
{
    /**
     * Implement run method due to c-class wrapper abstract specifications
     *
     * @return void
     */
    public function run();

    /**
     * Sets the socket classname to instantiate for this connection pool
     *
     * @param $socketClassName
     */
    public function __construct($socketClassName);

    /**
     * Return's the socket class name to instance for connections
     *
     * @return string
     */
    public function getSocketClassName();

    /**
     * Return's a connection instance bundled with resource reference by id
     *
     * @param int $connectionResourceId The connection resource id to bundle the connection instance with
     *
     * @return \TechDivision\WebServer\Sockets\SocketInterface The ready to use connection instance
     */
    public function get($connectionResourceId);

    /**
     * Add's a connection instance to pool
     *
     * @param \TechDivision\WebServer\Sockets\SocketInterface $connection The connection instance
     *
     * @return int The connection resource id for further usage
     */
    public function add(SocketInterface $connection);
}