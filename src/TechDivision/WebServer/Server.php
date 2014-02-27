<?php
/**
 * \TechDivision\WebServer\Server
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

use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\WebServer\Interfaces\ServerInterface;
use TechDivision\WebServer\Interfaces\ConfigInterface;

/**
 * Class Server
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Server implements ServerInterface
{

    /**
     * Hold's the config instance
     *
     * @var \TechDivision\WebServer\Interfaces\ConfigInterface The config instance
     */
    protected $config;

    /**
     * Constructs the server instance
     *
     * @param \TechDivision\WebServer\Interfaces\ConfigInterface $config The config instance
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Return's the server context
     *
     * @return \TechDivision\WebServer\Interfaces\ServerContextInterface The server's context instance
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }

    /**
     * Return's the config instance
     *
     * @return \TechDivision\WebServer\Interfaces\ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Start's the server's worker as defined in configuration
     *
     * @return void
     */
    public function run()
    {
        // init config var for shorter calls
        $config = $this->getConfig();

        // init connection handler
        $serverContext = new ServerContext($config);
        $serverContext->init();

        // setup workers
        for ($i=0; $i < $config->getWorkerNumber(); ++$i) {
            $workers[$i] = new Worker($serverContext);
        }

        // wait for all workers to stop
        foreach ($workers as $worker) {
            $worker->join();
        }
    }
}