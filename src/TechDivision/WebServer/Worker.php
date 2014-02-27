<?php
/**
 * \TechDivision\WebServer\Worker
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
use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\WebServer\Interfaces\WorkerInterface;

/**
 * Class Worker
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Worker extends \Thread implements WorkerInterface
{

    /**
     * Constructs the worker by setting the server context
     *
     * @param ServerContextInterface $serverContext The server's context
     */
    public function __construct($serverContext)
    {
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
        require WEBSERVER_BASEDIR . '../vendor/autoload.php';
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
        $serverContext = $this->getServerContext();
        $serverConnection = $serverContext->getServerConnection();

        // accept http connections
        while($connectionId = $serverContext->getConnectionPool()->add($serverConnection->accept())) {

            $connection = $serverContext->getConnectionPool()->get($connectionId);

            try {
                $connection->readLine();
            } catch (\Exception $e) {

            }

            $connection->write(
                "HTTP/1.1 200 OK" . PHP_EOL .
                "Server: phpwebserver" . PHP_EOL .
                "Content-Length: 16" . PHP_EOL .
                "Connection: close" . PHP_EOL .
                "Content-Type: text/html" . PHP_EOL .
                PHP_EOL .
                "1234567890123456"
            );

            $connection->close();

            // $httpConnection->negotiate();
        }

    }

}
