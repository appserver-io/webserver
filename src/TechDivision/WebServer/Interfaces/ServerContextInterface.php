<?php
/**
 * \TechDivision\WebServer\Interfaces\ServerContextInterface
 *
 * PHP version 5
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace TechDivision\WebServer\Interfaces;

use \TechDivision\WebServer\Interfaces\ServerConfigurationInterface;

/**
 * Interface ServerContextInterface
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
interface ServerContextInterface
{

    /**
     * Initialises the server context
     *
     * @param \TechDivision\WebServer\Interfaces\ServerConfigurationInterface $serverConfig The servers configuration instance
     *
     * @return void
     */
    public function init(ServerConfigurationInterface $serverConfig);

    /**
     * Return's the server config instance
     *
     * @return \TechDivision\WebServer\Interfaces\ServerConfigurationInterface The server config instance
     */
    public function getServerConfig();

    /**
     * Return's the server connection instance
     *
     * @param resource $connectionResource The socket resource
     *
     * @return SocketInterface The server connection instance
     */
    public function getConnectionInstance($connectionResource);

    /**
     * Return's an array of pre init modules instances
     *
     * @return array
     */
    public function getModules();

    /**
     * Return's an array of pre init connection handler instances
     *
     * @return array
     */
    public function getConnectionHandlers();
}
