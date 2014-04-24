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
use TechDivision\Http\HttpProtocol;
use TechDivision\WebServer\Dictionaries\ServerVars;
use TechDivision\WebServer\Interfaces\ConfigInterface;
use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\WebServer\Interfaces\ServerInterface;
use TechDivision\WebServer\Interfaces\WorkerInterface;
use TechDivision\WebServer\Exceptions\ModuleNotFoundException;
use TechDivision\WebServer\Exceptions\ConnectionHandlerNotFoundException;
use TechDivision\WebServer\RequestHandlerThread;

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
     * Hold's the serer connection resource
     *
     * @var resource
     */
    protected $serverConnectionResource;

    /**
     * Holds the server context object
     *
     * @var \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Hold's an array of connection handlers to use
     *
     * @var array
     */
    protected $connectionHandlers;

    /**
     * Flag if worker should be restarted by server
     *
     * @var bool
     */
    public $shouldRestart;

    /**
     * Constructs the worker by setting the server context
     *
     * @param resource                                                  $serverConnectionResource The server's file descriptor resource
     * @param \TechDivision\WebServer\Interfaces\ServerContextInterface $serverContext            The server's context
     * @param array                                                     $connectionHandlers       An array of connection handlers to use
     */
    public function __construct($serverConnectionResource, ServerContextInterface $serverContext, array $connectionHandlers)
    {
        $this->serverConnectionResource = $serverConnectionResource;
        // connection context init
        $this->serverContext = $serverContext;
        // connection handler init
        $this->connectionHandlers = $connectionHandlers;
        // autostart worker
        $this->start(PTHREADS_INHERIT_ALL | PTHREADS_ALLOW_HEADERS);
    }

    /**
     * Return's an array of connection handlers to use
     *
     * @return array
     */
    public function getConnectionHandlers()
    {
        return $this->connectionHandlers;
    }

    /**
     * Return's the server context instance
     *
     * @return \TechDivision\WebServer\Interfaces\ServerContextInterface The server's context
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
     * Starts the worker doing logic.
     *
     * @return void
     */
    public function run()
    {   
        // set current dir to base dir for relative dirs
        chdir(WEBSERVER_BASEDIR);
        // setup environment for worker
        require WEBSERVER_AUTOLOADER;
        // register shutdown handler
        register_shutdown_function(array(&$this, "shutdown"));
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
        
        try {
            
            // set should restart initial flag
            $this->shouldRestart = false;

            // get server context
            $serverContext = $this->getServerContext();

            // get server connection
            $serverConnection = $serverContext->getConnectionInstance($this->serverConnectionResource);

            // get connection handlers
            $connectionHandlers = $this->getConnectionHandlers();

            // init connection count
            $connectionCount = 0;
            $connectionLimit = rand(16, 64);

            // while worker not reached connection limit accept connections and process
            while (++$connectionCount < $connectionLimit) {

                // accept connections and process working connection by handlers
                if (($connection = $serverConnection->accept()) !== false) {

                    /**
                     * This is for testing async request processing only.
                     *
                     * It'll delegate the request handling to another thread which will be processed async.
                     *
                    // call async request handler to handle connection
                    $requestHandler = new RequestHandlerThread(
                        $connection->getConnectionResource(),
                        $connectionHandlers,
                        $serverContext,
                        $this
                    );
                    */

                    // iterate all connection handlers to handle connection right
                    foreach ($connectionHandlers as $connectionHandler) {
                        // if connectionHandler handled connection than break out of foreach
                        if ($connectionHandler->handle($connection, $this)) {
                            break;
                        }
                    }

                }
                // init context vars afterwards to avoid performance issues
                $serverContext->initVars();
            }
        } catch (\Exception $e) {
            // log error
            $serverContext->getLogger()->error($e->__toString());
        }

        // call internal shutdown
        $this->shutdown();
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
        // check if there was a fatal error caused shutdown
        $lastError = error_get_last();
        if ($lastError['type'] === E_ERROR || $lastError['type'] === E_USER_ERROR) {
            // log error
            $this->getServerContext()->getLogger()->error($lastError['message']);
        }
        $this->shouldRestart = true;
    }

    /**
     * Return's if worker should be restarted by server
     *
     * @return bool
     */
    public function shouldRestart()
    {
        return $this->shouldRestart;
    }
}
