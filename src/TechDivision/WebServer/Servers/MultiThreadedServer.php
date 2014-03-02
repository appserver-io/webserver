<?php
/**
 * \TechDivision\WebServer\Servers\MultiThreadedServer
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
     * Hold's the config instance
     *
     * @var \TechDivision\WebServer\Interfaces\ServerConfigurationInterface The server config instance
     */
    protected $serverConfig;

    /**
     * Constructs the server instance
     *
     * @param \TechDivision\WebServer\Interfaces\ServerConfigurationInterface $serverConfig The server config instance
     */
    public function __construct(ServerConfigurationInterface $serverConfig)
    {
        // set config
        $this->serverConfig = $serverConfig;
        // start server thread
        $this->start();
    }

    /**
     * Return's the config instance
     *
     * @return \TechDivision\WebServer\Interfaces\ServerConfigurationInterface
     */
    public function getServerConfig()
    {
        return $this->serverConfig;
    }

    /**
     * Start's the server's worker as defined in configuration
     *
     * @return void
     */
    public function run()
    {
        // setup autoloader
        require WEBSERVER_BASEDIR . '../vendor/autoload.php';

        // init config var for shorter calls
        $serverConfig = $this->getServerConfig();

        // get class names
        $socketType = $serverConfig->getSocketType();
        $serverContextType = $serverConfig->getServerContextType();
        $workerType = $serverConfig->getWorkerType();

        // init server context
        $serverContext = new $serverContextType();
        $serverContext->init($serverConfig);

        // init stream context for server connection
        $streamContext = stream_context_create();
        // check if ssl server config
        if ($serverConfig->getTransport() === 'ssl') {
            stream_context_set_option($streamContext, 'ssl', 'local_cert', WEBSERVER_BASEDIR . $this->getServerConfig()->getCertPath());
            stream_context_set_option($streamContext, 'ssl', 'passphrase', $this->getServerConfig()->getPassphrase());
            stream_context_set_option($streamContext, 'ssl', 'allow_self_signed', true);
            stream_context_set_option($streamContext, 'ssl', 'verify_peer', false);
        }

        // setup server bound on local adress
        $serverConnection = $socketType::getServerInstance(
            $serverConfig->getTransport() . '://' . $serverConfig->getAddress() . ':' . $serverConfig->getPort(),
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $streamContext
        );

        // setup and start workers
        for ($i=0; $i < $serverConfig->getWorkerNumber(); ++$i) {
            $workers[$i] = new $workerType($serverConnection->getConnectionResource(), $serverContext);
        }

        // wait until all workers finished
        foreach ($workers as $worker) {
            $worker->join();
        }
    }
}
