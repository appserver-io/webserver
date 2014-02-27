<?php
/**
 * \TechDivision\WebServer\ServerContext
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

use TechDivision\WebServer\ConnectionPool;
use TechDivision\WebServer\Interfaces\ConfigInterface;
use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\WebServer\Sockets\SocketInterface;
use TechDivision\WebServer\Interfaces\PoolInterface;

/**
 * Class ServerContext
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class ServerContext extends \Stackable implements ServerContextInterface
{

    /**
     * Hold's the config instance
     *
     * @var \TechDivision\WebServer\Interfaces\ConfigInterface
     */
    public $config;

    /**
     * Hold's the server connection resource id
     *
     * @var int
     */
    public $serverConnectionId;

    /**
     * Construct the server context
     *
     * @param \TechDivision\WebServer\Interfaces\ConfigInterface $config The config instance
     */
    public function __construct($config)
    {
        // init config
        $this->config = $config;
    }

    /**
     * Return's the config instance
     *
     * @return \TechDivision\WebServer\Interfaces\ConfigInterface The config instance
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set's the connectionPool to use
     *
     * @param \TechDivision\WebServer\Interfaces\PoolInterface $connectionPool
     *
     * @return void
     */
    public function setConnectionPool($connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    /**
     * Return's the connection pool instance
     *
     * @return \TechDivision\WebServer\Interfaces\PoolInterface
     */
    public function getConnectionPool()
    {
        return $this->connectionPool;
    }

    /**
     * Initialises the server context
     *
     * @return void
     */
    public function init()
    {
        // get connection class name for further instantiations
        $socketClassName = $this->getConfig()->getSocketClassName();

        // setup up connection Pool
        $this->setConnectionPool(
            new ConnectionPool($socketClassName)
        );

        // setup and add server connection to connection pool
        $this->setServerConnectionId(
            $this->getConnectionPool()->add(
                $socketClassName::getServerInstance(
                    $this->getConfig()->getServerListen() . ':' . $this->getConfig()->getServerPort()
                )
            )
        );
    }

    /**
     * Set's the server connection id
     *
     * @param int $id The id of the server connection
     */
    public function setServerConnectionId($id)
    {
        $this->serverConnectionId = $id;
    }

    /**
     * Return's the server connection id
     *
     * @return int The id of the server connection
     */
    public function getServerConnectionId()
    {
        return $this->serverConnectionId;
    }

    /**
     * Return's the server connection instance
     *
     * @return SocketInterface The server connection instance
     */
    public function getServerConnection()
    {
        return $this->getConnectionPool()->get(
            $this->getServerConnectionId()
        );
    }

    /**
     * Implement run method due to c-class wrapper abstract specifications
     *
     * @return void
     */
    public function run()
    {
        // do nothing
    }
}
