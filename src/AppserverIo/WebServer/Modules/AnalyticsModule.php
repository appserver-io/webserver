<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
namespace AppserverIo\WebServer\Modules;

use AppserverIo\Psr\HttpMessage\RequestInterface;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Dictionaries\ModuleVars;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\WebServer\Interfaces\HttpModuleInterface;

/**
 * AppserverIo\WebServer\Modules\Analytics
 *
 * Module which allows to track calls to certain locations on the server and react with a configured analytics connector
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class AnalyticsModule implements HttpModuleInterface
{

    /**
     * All analytics entries which we have to check for possible steps to take
     *
     * @var array $analytics
     */
    protected $analytics = array();

    /**
     * The server's context instance which we preserve for later use
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext $serverContext
     */
    protected $serverContext;

    /**
     * The requests's context instance
     *
     * @var \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext The request's context instance
     */
    protected $requestContext;

    /**
     *
     * @var array $dependencies The modules we depend on
     */
    protected $dependencies = array();

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'analytics';

    /**
     * Return's an array of module names which should be executed first
     *
     * @return array The array of module names
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Returns the module name
     *
     * @return string The module name
     */
    public function getModuleName()
    {
        return self::MODULE_NAME;
    }

    /**
     * Initiates the module
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
        try {
            $this->serverContext = $serverContext;
            $this->analytics = $serverContext->getServerConfig()->getAnalytics();
        } catch (\Exception $e) {
            // Re-throw as a ModuleException
            throw new ModuleException($e);
        }
    }

    /**
     * Prepares the module for upcoming request in specific context
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function prepare()
    {
        // nothing to do here
    }

    /**
     * Implement's module logic for given hook
     *
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface          $request        A request object
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface         $response       A response object
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     * @param int                                                    $hook           The current hook to process logic for
     *
     * @return boolean
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function process(RequestInterface $request, ResponseInterface $response, RequestContextInterface $requestContext, $hook)
    {
        try {
            // if false hook is coming do nothing
            if (ModuleHooks::RESPONSE_POST !== $hook) {
                return;
            }

            // get default analytics definitions
            $analytics = $this->analytics;

            // check if there are some volatile access definitions so use them and override global accesses
            if ($requestContext->hasModuleVar(ModuleVars::VOLATILE_ANALYTICS)) {
                // reset by volatile accesses
                $analytics = array_merge($analytics, $requestContext->getModuleVar(ModuleVars::VOLATILE_ANALYTICS));
            }

            // check all analytics and check if the uri matches
            foreach ($analytics as $analytic) {
                // run through our connectors if the if the URI matches
                $matches = array();
                if (preg_match('/' . $analytic['uri'] . '/', $requestContext->getServerVar(ServerVars::X_REQUEST_URI), $matches)) {
                    // we only need the matching parts of the URI
                    unset($matches[0]);
                    // prepare the matches for later usage
                    $backreferenceKeys = array();
                    foreach ($matches as $key => $match) {
                        $backreferenceKeys[] = '$' . $key;
                    }

                    // iterate over all connectors and call their services
                    foreach ($analytic['connectors'] as $connector) {
                        // iterate all params and fill in the regex backreferences
                        foreach ($connector['params'] as $key => $param) {
                            // if the param might contain backreferences we will replace them
                            if (strpos($param, '$') !== false) {
                                $connector['params'][$key] = str_replace($backreferenceKeys, $matches, $param);
                            }
                        }

                        // make a new connector instance, initialize it and make the call to its service
                        $connectorClass = str_replace('\\\\', '\\', $connector['type']);
                        if (class_exists($connectorClass)) {
                            // create the connector an make the call through it
                            $connectorInstance = new $connectorClass($this->serverContext);
                            $connectorInstance->init($connector['params']);
                            $connectorInstance->call($request, $response, $requestContext);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Re-throw as a ModuleException
            throw new ModuleException($e);
        }
    }
}
