<?php
/**
 * \TechDivision\WebServer\Interfaces\ServerContextInterface
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
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
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
 * @link       https://github.com/techdivision/TechDivision_WebServer
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

    /**
     * Set's a value to specific server var
     *
     * @param string $serverVar The server var to set
     * @param string $value     The value to server var
     *
     * @return void
     */
    public function setServerVar($serverVar, $value);

    /**
     * Return's a value for specific server var
     *
     * @param string $serverVar The server var to get value for
     *
     * @return string The value to given server var
     */
    public function getServerVar($serverVar);

    /**
     * Check's if value exists for given server var
     *
     * @param string $serverVar The server var to check
     *
     * @return bool Weather it has serverVar (true) or not (false)
     */
    public function hasServerVar($serverVar);

    /**
     * Return's all the server vars as array key value pair format
     *
     * @return array The server vars as array
     */
    public function getServerVars();
}
