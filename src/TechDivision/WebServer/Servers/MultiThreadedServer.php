<?php
/**
 * \TechDivision\WebServer\Servers\MultiThreadedServer
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
 * @subpackage Servers
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Servers;

use TechDivision\WebServer\Interfaces\ServerConfigurationInterface;
use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\WebServer\Interfaces\ServerInterface;
use TechDivision\WebServer\Interfaces\ConfigInterface;
use TechDivision\WebServer\Exceptions\ModuleNotFoundException;
use TechDivision\WebServer\Exceptions\ConnectionHandlerNotFoundException;

/**
 * Class MultiThreadedServer
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Servers
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

class MultiThreadedServer extends \Thread implements ServerInterface
{

    /**
     * Hold's the server context instance
     *
     * @var \TechDivision\WebServer\Interfaces\ServerContextInterface The server context instance
     */
    protected $serverContext;

    /**
     * Constructs the server instance
     *
     * @param \TechDivision\WebServer\Interfaces\ServerContextInterface $serverContext The server context instance
     */
    public function __construct(ServerContextInterface $serverContext)
    {
        // set context
        $this->serverContext = $serverContext;
        // start server thread
        $this->start();
    }

    /**
     * Return's the config instance
     *
     * @return \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }

    /**
     * Start's the server's worker as defined in configuration
     *
     * @return void
     *
     * @throws \TechDivision\WebServer\Exceptions\ModuleNotFoundException
     * @throws \TechDivision\WebServer\Exceptions\ConnectionHandlerNotFoundException
     */
    public function run()
    {
        // set current dir to base dir for relative dirs
        chdir(WEBSERVER_BASEDIR);

        // setup autoloader
        require WEBSERVER_AUTOLOADER;

        // init server context
        $serverContext = $this->getServerContext();

        // init config var for shorter calls
        $serverConfig = $serverContext->getServerConfig();

        $serverContext->getLogger()->debug(
            sprintf('Starting server %s...', $serverConfig->getName())
        );

        // get class names
        $socketType = $serverConfig->getSocketType();
        $workerType = $serverConfig->getWorkerType();

        // set socket backlog to 1024 for perform many concurrent connections
        $opts = array(
            'socket' => array(
                'backlog' => 1024,
            )
        );

        // init stream context for server connection
        $streamContext = stream_context_create($opts);
        // check if ssl server config
        if ($serverConfig->getTransport() === 'ssl') {
            stream_context_set_option($streamContext, 'ssl', 'local_cert', WEBSERVER_BASEDIR . $serverConfig->getCertPath());
            stream_context_set_option($streamContext, 'ssl', 'passphrase', $serverConfig->getPassphrase());
            stream_context_set_option($streamContext, 'ssl', 'allow_self_signed', true);
            stream_context_set_option($streamContext, 'ssl', 'verify_peer', false);
        }

        // setup server bound on local adress
        $serverConnection = $socketType::getServerInstance(
            $serverConfig->getTransport() . '://' . $serverConfig->getAddress() . ':' . $serverConfig->getPort(),
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $streamContext
        );

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

        // setup and start workers
        for ($i=1; $i <= $serverConfig->getWorkerNumber(); ++$i) {
            $workers[$i] = new $workerType(
                $serverConnection->getConnectionResource(),
                $serverContext,
                $connectionHandlers
            );
        }

        // todo: switch this to any controller that maintains an server thread
        $serverUp = true;

        // watch dog for all workers to restart if it's needed while server is up
        while ($serverUp === true) {
            // iterate all workers
            for ($i=1; $i <= $serverConfig->getWorkerNumber(); ++$i) {
                // check if worker should be restarted
                if ($workers[$i]->shouldRestart()) {
                    // unset origin worker ref
                    unset($workers[$i]);
                    // build up and start new worker instance
                    $workers[$i] = new $workerType(
                        $serverConnection->getConnectionResource(),
                        $serverContext,
                        $connectionHandlers
                    );
                }
            }
            // little sleep to avoid cpu burning
            usleep(100000);
        }
    }
}
