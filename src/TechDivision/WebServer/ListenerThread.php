<?php
/**
 * \TechDivision\WebServer\ListenerThread
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

/**
 * Class ListenerThread
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class ListenerThread extends \Thread
{

    public $connectionResource;

    public function __construct($socketClassName, $connectionResource, ConfigInterface $config)
    {
        $this->socketClassName = $socketClassName;
        $this->connectionResource = $connectionResource;
        $this->config = $config;
    }

    public function run()
    {
        require WEBSERVER_BASEDIR . '../vendor/autoload.php';

        $config = $this->config;
        $socketClassName = $this->socketClassName;
        $socketServer = $socketClassName::getInstance($this->connectionResource);

        $connectionClassName = $config->getConnectionClassName();
        $parserClassName = $config->getParserClassName();
        $requestClassName= $config->getRequestClassName();
        $responseClassName= $config->getResponseClassName();

        // setup parser
        $parser = new $parserClassName(new $requestClassName(), new $responseClassName());



        // accept http connections
        while($id = ConnectionContext::addConnection(new $connectionClassName($socketServer->accept(), $parser))) {
            ConnectionContext::process($id);
            //$httpConnection->negotiate();
        }

    }

}
