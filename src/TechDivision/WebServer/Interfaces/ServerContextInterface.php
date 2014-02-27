<?php
/**
 * \TechDivision\WebServer\Interfaces\ServerContextInterface
 *
 * PHP version 5
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace TechDivision\WebServer\Interfaces;

/**
 * Interface ServerContextInterface
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
interface ServerContextInterface
{

    /**
     * Return's the config instance
     *
     * @return \TechDivision\WebServer\Interfaces\ConfigInterface The config instance
     */
    public function getConfig();

    /**
     * Set's the connectionPool to use
     *
     * @param \TechDivision\WebServer\Interfaces\PoolInterface $connectionPool
     *
     * @return void
     */
    public function setConnectionPool($connectionPool);

    /**
     * Return's the connection pool instance
     *
     * @return \TechDivision\WebServer\Interfaces\PoolInterface
     */
    public function getConnectionPool();

    /**
     * Initialises the server context
     *
     * @return void
     */
    public function init();

    /**
     * Set's the server connection id
     *
     * @param int $id The id of the server connection
     */
    public function setServerConnectionId($id);
    /**
     * Return's the server connection id
     *
     * @return int The id of the server connection
     */
    public function getServerConnectionId();

    /**
     * Return's the server connection instance
     *
     * @return SocketInterface The server connection instance
     */
    public function getServerConnection();

    /**
     * Implement run method due to c-class wrapper abstract specifications
     *
     * @return void
     */
    public function run();

}
