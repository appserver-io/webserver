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

use Psr\Log\LoggerInterface;
use TechDivision\WebServer\Interfaces\ServerConfigurationInterface;
use TechDivision\WebServer\Dictionaries\EnvVars;
use TechDivision\WebServer\Sockets\SocketInterface;

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
     * Injects a third party container
     *
     * @param mixed $container The container to inject
     *
     * @return mixed
     */
    public function injectContainer($container);

    /**
     * Injects a Psr compatible logger instance
     *
     * @param \Psr\Log\LoggerInterface[] $loggers The array of logger instances
     *
     * @return void
     */
    public function injectLoggers(array $loggers);

    /**
     * Return's the server config instance
     *
     * @return \TechDivision\WebServer\Interfaces\ServerConfigurationInterface The server config instance
     */
    public function getServerConfig();

    /**
     * Return's the logger instance
     *
     * @param string $loggerType the logger's type to get
     *
     * @return \Psr\Log\LoggerInterface|null The logger instance
     * @throws \TechDivision\WebServer\Exceptions\ServerException
     */
    public function getLogger($loggerType = EnvVars::LOGGER_SYSTEM);

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

    /**
     * Init basic server vars
     *
     * @return void
     */
    public function initServerVars();

    /**
     * Set's a value to specific module var
     *
     * @param string $moduleVar The module var to set
     * @param string $value     The value to module var
     *
     * @return void
     */
    public function setModuleVar($moduleVar, $value);

    /**
     * Return's a value for specific module var
     *
     * @param string $moduleVar The module var to get value for
     *
     * @throws \TechDivision\WebServer\Exceptions\ServerException
     *
     * @return mixed The value to given module var
     */
    public function getModuleVar($moduleVar);

    /**
     * Return's all the module vars as array key value pair format
     *
     * @return array The module vars as array
     */
    public function getModuleVars();

    /**
     * Check's if value exists for given module var
     *
     * @param string $moduleVar The module var to check
     *
     * @return boolean Weather it has moduleVar (true) or not (false)
     */
    public function hasModuleVar($moduleVar);

    /**
     * Will init the module vars, which means we will clean it completely
     *
     * @return void
     */
    public function initModuleVars();

    /**
     * Add's connection relevant informations to server vars
     *
     * @param \TechDivision\WebServer\Sockets\SocketInterface $connection The connection
     *
     * @return void
     */
    public function setConnectionServerVars(SocketInterface $connection);

    /**
     * Sets a value to specific env var
     *
     * @param string $envVar The env var to set
     * @param string $value  The value to env var
     *
     * @return void
     */
    public function setEnvVar($envVar, $value);

    /**
     * Unsets a specific env var
     *
     * @param string $envVar The env var to unset
     *
     * @return void
     */
    public function unsetEnvVar($envVar);

    /**
     * Return's a value for specific env var
     *
     * @param string $envVar The env var to get value for
     *
     * @throws \TechDivision\WebServer\Exceptions\ServerException
     *
     * @return mixed The value to given env var
     */
    public function getEnvVar($envVar);

    /**
     * Return's all the env vars as array key value pair format
     *
     * @return array The env vars as array
     */
    public function getEnvVars();

    /**
     * Check's if value exists for given env var
     *
     * @param string $envVar The env var to check
     *
     * @return boolean Weather it has envVar (true) or not (false)
     */
    public function hasEnvVar($envVar);

    /**
     * Will init the env vars, which means we will clean it completely
     *
     * @return void
     */
    public function initEnvVars();

    /**
     * Resets all var used in server context
     *
     * @return void
     */
    public function initVars();
}
