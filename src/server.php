<?php
/**
 * server.php
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

/**
 * Returns if php build is with thread safe options
 *
 * @return boolean True if PHP is thread safe
 */
function isThreadSafe()
{
    ob_start();
    phpinfo(INFO_GENERAL);
    return preg_match('/thread safety => enabled/i', strip_tags(ob_get_clean()));
}

if (!isThreadSafe()) {
    die('This php build is not thread safe. Please recompile with option --enable-maintainer-zts' . PHP_EOL);
}
if (!extension_loaded('appserver')) {
    die('Required php extension "appserver" not found. See https://github.com/techdivision/php-ext-appserver' . PHP_EOL);
}
if (!extension_loaded('pthreads')) {
    die('Required php extension "appserver" not found. See https://github.com/krakjoe/pthreads' . PHP_EOL);
}

define('WEBSERVER_BASEDIR', __DIR__ . DIRECTORY_SEPARATOR);
define('WEBSERVER_AUTOLOADER', WEBSERVER_BASEDIR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

require WEBSERVER_AUTOLOADER;

// set current dir to base dir for relative dirs
chdir(WEBSERVER_BASEDIR);

// check if user defined configuration is passed via argv
if (isset($argv[1])) {
    define('WEBSERVER_CONFIGFILE', $argv[1]);
} else {
    define('WEBSERVER_CONFIGFILE', WEBSERVER_BASEDIR . 'etc' . DIRECTORY_SEPARATOR . 'phpwebserver.xml');
}

// check which config format should be used based on file extension
if ($configType = str_replace('.', '', strrchr(WEBSERVER_CONFIGFILE, '.'))) {
    $mainConfigurationType = '\TechDivision\WebServer\Configuration\Main' . ucfirst($configType) . 'Configuration';
    // try to instantiate configuration type based on file
    if (class_exists($mainConfigurationType)) {
        $mainConfiguration = new $mainConfigurationType(WEBSERVER_CONFIGFILE);
    } else {
        die("Configuration file '$configType' is not valid or not found.". PHP_EOL);
    }
} else {
    die("No valid configuration file given." . PHP_EOL);
}

// init loggers
$loggers = array();
foreach ($mainConfiguration->getLoggerConfigs() as $loggerConfig) {

    // init processors
    $processors = array();
    foreach ($loggerConfig->getProcessors() as $processorType) {
        // create processor
        $processors[] = new $processorType();
    }
    // init handlers
    $handlers = array();
    foreach ($loggerConfig->getHandlers() as $handlerType => $handlerData) {
        // create handler
        $handlerTypeClass = new ReflectionClass($handlerType);
        $handler = $handlerTypeClass->newInstanceArgs($handlerData["params"]);
        if (isset($handlerData['formatter'])) {
            $formatterData = $handlerData['formatter'];
            $formatterType = $formatterData['type'];
            // create formatter
            $formatterClass = new ReflectionClass($formatterType);
            $formatter = $formatterClass->newInstanceArgs($formatterData['params']);
            // set formatter to logger
            $handler->setFormatter($formatter);
        }
        $handlers[] = $handler;
    }

    // get logger type
    $loggerType = $loggerConfig->getType();
    // init logger instance
    $logger = new $loggerType($loggerConfig->getName(), $handlers, $processors);

    $logger->debug(sprintf('logger initialised: %s (%s)', $loggerConfig->getName(), $loggerType));

    // set logger by name
    $loggers[$loggerConfig->getName()] = $logger;
}

// init servers
$servers = array();
foreach ($mainConfiguration->getServerConfigs() as $serverConfig) {

    // get type definitions
    $serverType = $serverConfig->getType();
    $serverContextType = $serverConfig->getServerContextType();

    // init server context
    $serverContext = new $serverContextType();
    $serverContext->init($serverConfig);

    // check if logger name exists
    if (isset($loggers[$serverConfig->getLoggerName()])) {
        $serverContext->injectLoggers($loggers);
    } else {
        throw new \Exception(sprintf('Logger %s not found.', $serverConfig->getLoggerName()));
    }

    // init and start server
    $servers[] = new $serverType($serverContext);
}

// wait for servers
foreach ($servers as $server) {
    $server->join();
}
