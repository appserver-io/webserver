<?php
/**
 * \TechDivision\WebServer\ServerContext
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer;

use Psr\Log\LoggerInterface;
use TechDivision\WebServer\ConnectionPool;
use TechDivision\WebServer\Dictionaries\EnvVars;
use TechDivision\WebServer\Exceptions\ConnectionHandlerNotFoundException;
use TechDivision\WebServer\Exceptions\ModuleNotFoundException;
use TechDivision\WebServer\Exceptions\ServerException;
use TechDivision\WebServer\Interfaces\ConfigInterface;
use TechDivision\WebServer\Interfaces\ServerConfigurationInterface;
use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\WebServer\Modules\CoreModule;
use TechDivision\WebServer\Modules\DirectoryModule;
use TechDivision\WebServer\Sockets\SocketInterface;
use TechDivision\WebServer\Interfaces\PoolInterface;
use TechDivision\WebServer\Dictionaries\ServerVars;

/**
 * Class ServerContext
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/TechDivision_WebServer
 */
class ServerContext implements ServerContextInterface
{

    /**
     * Optionally hold's an container implementation of third party environment.
     * So every mod depending on his environment can use this as a container to transfer environment specific stuff.
     *
     * @var mixed
     */
    protected $container;

    /**
     * All logger instances will be hold here.
     * Every logger instance has to be a PSR compatible
     *
     * @var LoggerInterface[]
     */
    protected $loggers;

    /**
     * Hold's the config instance
     *
     * @var \TechDivision\WebServer\Interfaces\ServerConfigurationInterface $serverConfig
     */
    protected $serverConfig;

    /**
     * This member will hold the server variables which different modules can set/get in order to change the processing
     * of the incoming request.
     * This will also contain server variables as one might suspect in $_SERVER
     *
     * @var array $serverVars
     */
    protected $serverVars;

    /**
     * This member will hold the module variables which different modules can set/get to communicate with each
     * other without knowing each other.
     *
     * @var array $moduleVars
     */
    protected $moduleVars;

    /**
     * This member will hold the environment (env) variables which different modules can set/get to provide
     * the context similar to $_ENV in the plain php world
     *
     * @var array $envVars
     */
    protected $envVars;

    /**
     * Initialises the server context
     *
     * @param \TechDivision\WebServer\Interfaces\ServerConfigurationInterface $serverConfig The servers configuration
     *
     * @return void
     */
    public function init(ServerConfigurationInterface $serverConfig)
    {
        // set configuration
        $this->serverConfig = $serverConfig;

        // init env vars
        $this->initEnvVars();
        // init server vars
        $this->initServerVars();
        // init module vars
        $this->initModuleVars();

    }

    /**
     * Return's the server config instance
     *
     * @return \TechDivision\WebServer\Interfaces\ServerConfigurationInterface The server config instance
     */
    public function getServerConfig()
    {
        return $this->serverConfig;
    }

    /**
     * Return's the server connection instance
     *
     * @param resource $connectionResource The socket resource
     *
     * @return SocketInterface The server connection instance
     */
    public function getConnectionInstance($connectionResource)
    {
        $socketType = $this->getServerConfig()->getSocketType();

        return $socketType::getInstance($connectionResource);
    }

    /**
     * Injects the container for further use in specific webserver mods etc...
     *
     * @param mixed $container An container instance for third party environment
     *
     * @return void
     */
    public function injectContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Injects a Psr compatible logger instance
     *
     * @param \Psr\Log\LoggerInterface[] $loggers The array of logger instances
     *
     * @return void
     */
    public function injectLoggers(array $loggers)
    {
        $this->loggers = $loggers;
    }

    /**
     * Return's the container instance
     *
     * @return mixed The container instance for third party environment
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Return's the logger instance
     *
     * @param string $loggerType the logger's type to get
     *
     * @return \Psr\Log\LoggerInterface|null The logger instance
     * @throws \TechDivision\WebServer\Exceptions\ServerException
     */
    public function getLogger($loggerType = EnvVars::LOGGER_SYSTEM)
    {
        // check if there is information about this logger type in server vars first
        if ($this->hasEnvVar($loggerType)) {
            // get logger name from modulevars by type
            $loggerName = $this->getEnvVar($loggerType);
            // check if logger is set
            if (isset($this->loggers[$loggerName])) {
                // return logger
                return $this->loggers[$loggerName];
            }
            // throw exception
            throw new ServerException("Logger name '$loggerName' does not exist.", 500);
        }
    }

    /**
     * Set's a value to specific server var
     *
     * @param string $serverVar The server var to set
     * @param string $value     The value to server var
     *
     * @return void
     */
    public function setServerVar($serverVar, $value)
    {
        if (!is_null($value)) {
            $this->serverVars[$serverVar] = $value;
        }
    }

    /**
     * Unsets a specific server var
     *
     * @param string $serverVar The server var to unset
     *
     * @return void
     */
    public function unsetServerVar($serverVar)
    {
        if (isset($this->serverVars[$serverVar])) {
            unset($this->serverVars[$serverVar]);
        }
    }

    /**
     * Return's a value for specific server var
     *
     * @param string $serverVar The server var to get value for
     *
     * @throws \TechDivision\WebServer\Exceptions\ServerException
     *
     * @return string The value to given server var
     */
    public function getServerVar($serverVar)
    {
        // check if server var is set
        if (isset($this->serverVars[$serverVar])) {
            // return server vars value
            return $this->serverVars[$serverVar];
        }
        // throw exception
        throw new ServerException("Server var '$serverVar'' does not exist.", 500);
    }

    /**
     * Return's all the server vars as array key value pair format
     *
     * @return array The server vars as array
     */
    public function getServerVars()
    {
        return $this->serverVars;
    }

    /**
     * Check's if value exists for given server var
     *
     * @param string $serverVar The server var to check
     *
     * @return bool Weather it has serverVar (true) or not (false)
     */
    public function hasServerVar($serverVar)
    {
        // check if server var is set
        if (!isset($this->serverVars[$serverVar])) {
            return false;
        }

        return true;
    }

    /**
     * Init basic server vars
     *
     * @return void
     */
    public function initServerVars()
    {
        // set server vars to local var to shorter usage
        $serverSoftware = $this->getServerConfig()->getSoftware() . ' (PHP ' . PHP_VERSION . ')';
        $serverAddress = $this->getServerConfig()->getAddress();
        $serverPort = $this->getServerConfig()->getPort();

        // set document root
        $documentRoot = $this->getServerConfig()->getDocumentRoot();
        // check if relative path is given and make is absolute by using cwd as prefix
        if (substr($documentRoot, 0, 1) !== "/") {
            $documentRoot = getcwd() . DIRECTORY_SEPARATOR . $documentRoot;
        }

        // set server vars array
        $this->serverVars = array(
            ServerVars::DOCUMENT_ROOT => $documentRoot,
            ServerVars::SERVER_ADMIN => $this->getServerConfig()->getAdmin(),
            ServerVars::SERVER_NAME => $serverAddress,
            ServerVars::SERVER_ADDR => $serverAddress,
            ServerVars::SERVER_PORT => $serverPort,
            ServerVars::GATEWAY_INTERFACE => "PHP/" . PHP_VERSION,
            ServerVars::SERVER_SOFTWARE => $serverSoftware,
            ServerVars::SERVER_SIGNATURE =>
                "<address>$serverSoftware Server at $serverAddress Port $serverPort</address>\r\n",
            ServerVars::SERVER_HANDLER => CoreModule::MODULE_NAME,
            ServerVars::SERVER_ERRORS_PAGE_TEMPLATE_PATH => $this->getServerConfig()->getErrorsPageTemplatePath(),
            ServerVars::PATH => getenv('PATH'),
            ServerVars::HTTPS => ServerVars::VALUE_HTTPS_OFF
        );

        // check if ssl is going on and set server var for it like apache does
        if ($this->getServerConfig()->getTransport() === 'ssl') {
            $this->setServerVar(ServerVars::HTTPS, ServerVars::VALUE_HTTPS_ON);
        }
    }

    /**
     * Add's connection relevant information to server vars
     *
     * @param \TechDivision\WebServer\Sockets\SocketInterface $connection The connection
     *
     * @return void
     */
    public function setConnectionServerVars(SocketInterface $connection)
    {
        /**
         * fill server vars with connection info
         * Not yet implemented due to performance issues
         *
         * REMOTE_HOST
         * REMOTE_IDENT
         */
        $this->setServerVar(ServerVars::REMOTE_ADDR, $connection->getAddress());
        $this->setServerVar(ServerVars::REMOTE_PORT, $connection->getPort());
    }

    /**
     * Sets a value to specific module var
     *
     * @param string $moduleVar The module var to set
     * @param string $value     The value to module var
     *
     * @return void
     */
    public function setModuleVar($moduleVar, $value)
    {
        if (!is_null($value)) {
            $this->moduleVars[$moduleVar] = $value;
        }
    }

    /**
     * Unsets a specific module var
     *
     * @param string $moduleVar The module var to unset
     *
     * @return void
     */
    public function unsetModuleVar($moduleVar)
    {
        if (isset($this->moduleVars[$moduleVar])) {
            unset($this->moduleVars[$moduleVar]);
        }
    }

    /**
     * Return's a value for specific module var
     *
     * @param string $moduleVar The module var to get value for
     *
     * @throws \TechDivision\WebServer\Exceptions\ServerException
     *
     * @return mixed The value to given module var
     */
    public function getModuleVar($moduleVar)
    {
        // check if server var is set
        if (isset($this->moduleVars[$moduleVar])) {
            // return server vars value
            return $this->moduleVars[$moduleVar];
        }
        // throw exception
        throw new ServerException("Module var '$moduleVar'' does not exist.", 500);
    }

    /**
     * Return's all the module vars as array key value pair format
     *
     * @return array The module vars as array
     */
    public function getModuleVars()
    {
        return $this->moduleVars;
    }

    /**
     * Check's if value exists for given module var
     *
     * @param string $moduleVar The module var to check
     *
     * @return boolean Weather it has moduleVar (true) or not (false)
     */
    public function hasModuleVar($moduleVar)
    {
        // check if server var is set
        if (!isset($this->moduleVars[$moduleVar])) {
            return false;
        }

        return true;
    }

    /**
     * Will init the module vars, which means we will clean it completely
     *
     * @return void
     */
    public function initModuleVars()
    {
        // set module vars array
        $this->moduleVars = array();
    }

    /**
     * Sets a value to specific env var
     *
     * @param string $envVar The env var to set
     * @param string $value  The value to env var
     *
     * @return void
     */
    public function setEnvVar($envVar, $value)
    {
        if (!is_null($value)) {
            $this->envVars[$envVar] = $value;
        }
    }

    /**
     * Unsets a specific env var
     *
     * @param string $envVar The env var to unset
     *
     * @return void
     */
    public function unsetEnvVar($envVar)
    {
        if (isset($this->envVars[$envVar])) {
            unset($this->envVars[$envVar]);
        }
    }

    /**
     * Return's a value for specific env var
     *
     * @param string $envVar The env var to get value for
     *
     * @throws \TechDivision\WebServer\Exceptions\ServerException
     *
     * @return mixed The value to given env var
     */
    public function getEnvVar($envVar)
    {
        // check if server var is set
        if (isset($this->envVars[$envVar])) {
            // return server vars value
            return $this->envVars[$envVar];
        }
        // throw exception
        throw new ServerException("Env var '$envVar'' does not exist.", 500);
    }

    /**
     * Return's all the env vars as array key value pair format
     *
     * @return array The env vars as array
     */
    public function getEnvVars()
    {
        return $this->envVars;
    }

    /**
     * Check's if value exists for given env var
     *
     * @param string $envVar The env var to check
     *
     * @return boolean Weather it has envVar (true) or not (false)
     */
    public function hasEnvVar($envVar)
    {
        // check if server var is set
        if (!isset($this->envVars[$envVar])) {
            return false;
        }

        return true;
    }

    /**
     * Will init the env vars, which means we will clean it completely
     *
     * @return void
     */
    public function initEnvVars()
    {
        // init env vars array
        $this->envVars = array(
            EnvVars::LOGGER_SYSTEM => $this->getServerConfig()->getLoggerName(),
        );
    }
}
