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

    protected $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function run()
    {

        // init config var for shorter calls
        $config = $this->getConfig();

        $socketClassName = $config->getSocketClassName();

        // create server stream connection
        $socketServer = $socketClassName::getServerInstance(
            $config->getServerListen() . ':' . $config->getServerPort()
        );

        /*
        $connectionClassName = $config->getConnectionClassName();
        $parserClassName = $config->getParserClassName();
        $requestClassName= $config->getRequestClassName();
        $responseClassName= $config->getResponseClassName();

        // setup parser
        $parser = new $parserClassName(new $requestClassName(), new $responseClassName());

        // accept http connections
        while($httpConnection = new $connectionClassName($socketServer->accept(), $parser)) {
            $httpConnection->negotiate();
        }
        */

        // open listener threads sharing server connection and config
        for ($i=0; $i < 128; ++$i) {
            $listenerThreads[$i] = new ListenerThread(
                $socketClassName,
                $socketServer->getConnectionResource(),
                $config
            );
            $listenerThreads[$i]->start();
        }

        // wait for all listeners to stop
        foreach ($listenerThreads as $listenerThread) {
            $listenerThread->join();
        }

    }
}