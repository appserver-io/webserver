<?php
/**
 * \TechDivision\WebServer\Workers\ThreadWorker
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
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
use TechDivision\WebServer\Exceptions\ModuleNotFoundException;
use TechDivision\WebServer\Exceptions\ConnectionHandlerNotFoundException;

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
        $this->start(PTHREADS_INHERIT_ALL | PTHREADS_ALLOW_HEADERS);
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
     * Return's the server's connection resource ref
     *
     * @return resource
     */
    protected function getServerConnectionResource()
    {
        return $this->serverConnectionResource;
    }

    /**
     * Start's the worker doing logic
     *
     * @return void
     */
    public function run()
    {
        // set current dir to base dir for relative dirs
        chdir(WEBSERVER_BASEDIR);
        // setup environment for worker
        require WEBSERVER_AUTOLOADER;
        // do work
        $this->work();
    }

    /**
     * Implements the workers actual logic
     *
     * @return void
     *
     * @throws \TechDivision\WebServer\Exceptions\ModuleNotFoundException
     * @throws \TechDivision\WebServer\Exceptions\ConnectionHandlerNotFoundException
     */
    public function work()
    {
        // get server context
        $serverContext = $this->getServerContext();

        // get server config
        $serverConfig = $serverContext->getServerConfig();

        // get server connection
        $serverConnection = $serverContext->getConnectionInstance($this->serverConnectionResource);

        // init modules array
        $modules = array();
        // initiate server modules
        $moduleTypes = $serverConfig->getModules();
        foreach ($moduleTypes as $moduleType) {
            // check if module type exists
            if (!class_exists($moduleType)) {
                throw new ModuleNotFoundException($moduleType);
            }
            // instantiate module type
            $modules[$moduleType] = new $moduleType();
            // init module with serverContext (this)
            $modules[$moduleType]->init($serverContext);
        }

        // init connection handler array
        $connectionHandlers = array();
        // initiate server connection handlers
        $connectionHandlersTypes = $serverConfig->getConnectionHandlers();
        foreach ($connectionHandlersTypes as $connectionHandlerType) {
            // check if conenction handler type exists
            if (!class_exists($connectionHandlerType)) {
                throw new ConnectionHandlerNotFoundException($connectionHandlerType);
            }
            // instantiate connection handler type
            $connectionHandlers[$connectionHandlerType] = new $connectionHandlerType();
            // init connection handler with serverContext (this)
            $connectionHandlers[$connectionHandlerType]->init($serverContext);
            // inject modules
            $connectionHandlers[$connectionHandlerType]->injectModules($modules);
        }

        // accept connections and process connection by handler
        while ($connection = $serverConnection->accept()) {
            // iterate all connection handlers to handle connection right
            foreach ($connectionHandlers as $connectionHandler) {
                // if connectionHandler handled connection than break out of foreach
                if ($connectionHandler->handle($connection, $this)) {
                    break;
                }
            }
            // init server vars afterwards to avoid performance issues
            $serverContext->initServerVars();
        }
    }

    /**
     * Does shutdown logic for worker if something breaks in process.
     *
     * This shutdown function will be called from specific connection handler if an error occurs, so the connection
     * handler can send an response in the correct protocol specifications and a new worker can be started
     *
     * @return void
     */
    public function shutdown()
    {
        // start new thread worker before shutdown
        new ThreadWorker($this->getServerConnectionResource(), $this->getServerContext());
    }
}
