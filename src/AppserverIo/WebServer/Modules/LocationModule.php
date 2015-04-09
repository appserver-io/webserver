<?php

/**
 * \AppserverIo\WebServer\Modules\LocationModule
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules;

use AppserverIo\Http\HttpProtocol;
use AppserverIo\Http\HttpResponseStates;
use AppserverIo\Psr\HttpMessage\Protocol;
use AppserverIo\Psr\HttpMessage\RequestInterface;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Dictionaries\ModuleVars;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\WebServer\Interfaces\HttpModuleInterface;

/**
 * Module that creates a directory index, if no other module handles
 * the request. If you want to use this module, the modules have to be
 * configured in the following order:
 *
 * <module type="\AppserverIo\WebServer\Modules\VirtualHostModule"/>
 * <module type="\AppserverIo\WebServer\Modules\AuthenticationModule"/>
 * <module type="\AppserverIo\WebServer\Modules\EnvironmentVariableModule" />
 * <module type="\AppserverIo\WebServer\Modules\RewriteModule"/>
 * <module type="\AppserverIo\WebServer\Modules\DirectoryModule"/>
 * <module type="\AppserverIo\WebServer\Modules\AccessModule"/>
 * <module type="\AppserverIo\WebServer\Modules\LocationModule"/>
 * <module type="\AppserverIo\WebServer\Modules\AutoIndexModule"/>
 * <module type="\AppserverIo\WebServer\Modules\CoreModule"/>
 * <module type="\AppserverIo\WebServer\Modules\PhpModule"/>
 * <module type="\AppserverIo\WebServer\Modules\FastCgiModule"/>
 * <module type="\AppserverIo\Appserver\ServletEngine\ServletEngine" />
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class LocationModule implements HttpModuleInterface
{

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'location';

    /**
     * Holds an array of all locations.
     *
     * @var array $locations
     */
    protected $locations;

    /**
     * Defines the map from params to server vars
     *
     * @var array
     */
    protected $paramServerVarsMap = array(
        'admin' => ServerVars::SERVER_ADMIN,
        'documentRoot' => ServerVars::DOCUMENT_ROOT,
        'software' => ServerVars::SERVER_SOFTWARE,
        'autoIndex' => ServerVars::SERVER_AUTO_INDEX,
        'errorsPageTemplatePath' => ServerVars::SERVER_ERRORS_PAGE_TEMPLATE_PATH,
        'welcomPageTemplatePath' => ServerVars::SERVER_WELCOME_PAGE_TEMPLATE_PATH,
        'autoIndexTemplatePath' => ServerVars::SERVER_AUTO_INDEX_TEMPLATE_PATH
    );

    /**
     * Initiates the module.
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
        $this->locations = $serverContext->getServerConfig()->getLocations();
    }

    /**
     * Implements module logic for given hook
     *
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface          $request        A request object
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface         $response       A response object
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     * @param int                                                    $hook           The current hook to process logic for
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function process(RequestInterface $request, ResponseInterface $response, RequestContextInterface $requestContext, $hook)
    {

        /**
         * @var $request \AppserverIo\Psr\HttpMessage\RequestInterface
         */

        /**
         * @var $response \AppserverIo\Psr\HttpMessage\ResponseInterface
         */

        // if false hook is comming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }

        // load the locations
        $locations = $this->locations;

        // check if there are some volatile location definitions so use them and override global locations
        if ($requestContext->hasModuleVar(ModuleVars::VOLATILE_LOCATIONS)) {
            $locations = $requestContext->getModuleVar(ModuleVars::VOLATILE_LOCATIONS);
        }
        
        var_dump($locations);

        // query whether we've locations configured or not
        if (sizeof($locations) === 0) {
            return;
        }

        // initialize the array for the handlers
        $handlers = array();

        // load the actual request URI without query string
        $uriWithoutQueryString = $requestContext->getServerVar(ServerVars::X_REQUEST_URI);

        // process the all locations found for this request
        foreach ($locations as $location) {
            // query whether the location matches the acutal request URI
            if (preg_match('/' . $location['condition'] . '/', $uriWithoutQueryString)) {
                // query whether the location has file handlers configured for the actual URI
                if (isset($location['params'])) {
                    // iterate over all params and try to set as server var via mapping
                    foreach ($location['params'] as $paramName => $paramValue) {
                        // check if server var mapping exists
                        if (isset($this->paramServerVarsMap[$paramName])) {
                            // check if documentRoot is changed
                            if ($this->paramServerVarsMap[$paramName] === ServerVars::DOCUMENT_ROOT) {
                                // check if relative path is given and make is absolute by using cwd as prefix
                                if (substr($paramValue, 0, 1) !== "/") {
                                    $paramValue = getcwd() . DIRECTORY_SEPARATOR . $paramValue;
                                }
                            }
                            // set server var
                            $requestContext->setServerVar($this->paramServerVarsMap[$paramName], $paramValue);
                        }
                    }
                }

                // query whether the location has file handlers configured for the actual URI
                if (isset($location['handlers'])) {
                    $handlers = array_merge($handlers, $location['handlers']);
                }
            }
        }

        // add the handlers we have (if any)
        if (sizeof($handlers) !== 0) {
            $requestContext->setModuleVar(ModuleVars::VOLATILE_HANDLERS, $handlers);
        }
    }

    /**
     * Return's an array of module names which should be executed first
     *
     * @return array The array of module names
     */
    public function getDependencies()
    {
        return array();
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
     * Prepares the module for upcoming request in specific context
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function prepare()
    {
        // nothing to prepare for this module
    }
}
