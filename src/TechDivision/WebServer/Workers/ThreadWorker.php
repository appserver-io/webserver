<?php
/**
 * \TechDivision\WebServer\Workers\ThreadWorker
 *
 * PHP version 5
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Workers
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Workers;

use TechDivision\Http\HttpConnectionHandler;
use TechDivision\WebServer\Interfaces\ConfigInterface;
use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\WebServer\Interfaces\WorkerInterface;

/**
 * Class ThreadWorker
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Workers
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class ThreadWorker extends \Thread implements WorkerInterface
{

    /**
     * Constructs the worker by setting the server context
     *
     * @param resource               $serverConnectionResource The server's file descriptor resource
     * @param ServerContextInterface $serverContext            The server's context
     */
    public function __construct($serverConnectionResource, $serverContext)
    {
        $this->serverConnectionResource = $serverConnectionResource;
        // connection context init
        $this->serverContext = $serverContext;
        // autostart worker
        $this->start();
    }

    /**
     * Return's the server context instance
     *
     * @return ServerContextInterface The server's context
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }

    /**
     * Start's the worker doing logic
     *
     * @return void
     */
    public function run()
    {
        // setup environment for worker
        require WEBSERVER_AUTOLOADER;
        // do work
        $this->work();
    }

    /**
     * Implements the workers actual logic
     *
     * @return void
     */
    public function work()
    {
        // get server context
        $serverContext = $this->getServerContext();
        // get server connection
        $serverConnection = $serverContext->getConnectionInstance($this->serverConnectionResource);

        // accept connections and process connection by handler
        while ($connection = $serverConnection->accept()) {
            // iterate all connection handlers to handle connection right
            foreach ($serverContext->getConnectionHandlers() as $connectionHandler) {
                // if connectionHandler handled connection than break out of foreach
                if ($connectionHandler->handle($connection)) {
                    break;
                }
            }
        }

    }
}
