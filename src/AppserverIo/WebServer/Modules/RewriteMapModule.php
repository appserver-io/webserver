<?php

/**
 * \AppserverIo\WebServer\Modules\RewriteMapModule
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules;

use AppserverIo\Psr\HttpMessage\Protocol;
use AppserverIo\Psr\HttpMessage\RequestInterface;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\WebServer\Interfaces\HttpModuleInterface;
use AppserverIo\Http\HttpResponseStates;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Dictionaries\ModuleVars;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Server\Dictionaries\ServerVars;

/**
 * Class RewriteMapModule
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class RewriteMapModule implements HttpModuleInterface
{

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'rewriteMap';

    /**
     * Hold's the server context instance
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Return's the request instance
     *
     * @return \AppserverIo\Psr\HttpMessage\RequestInterface The request instance
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the response instance
     *
     * @return \AppserverIo\Psr\HttpMessage\ResponseInterface The response instance;
     */
    public function getResponse()
    {
        return $this->response;
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
        $this->serverContext = $serverContext;
        $this->rewriteMaps = $serverContext->getServerConfig()->getRewriteMaps();
    }

    /**
     * Return's the server context instance
     *
     * @return \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
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
        // In php an interface is, by definition, a fixed contract. It is immutable.
        // So we have to declair the right ones afterwards...
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

        // set req and res object internally
        $this->request = $request;
        $this->response = $response;

        // get default rewrite maps definitions
        $rewriteMaps = $this->rewriteMaps;

        // check if there are some volatile rewrite map definitions so add them
        if ($requestContext->hasModuleVar(ModuleVars::VOLATILE_REWRITE_MAPS)) {
            $volatileRewriteMaps = $requestContext->getModuleVar(ModuleVars::VOLATILE_REWRITE_MAPS);
            // merge rewrite maps
            $rewriteMaps = array_merge($volatileRewriteMaps, $this->rewriteMaps);
        }

        // check protocol to be either http or https when secure is going on
        $protocol = 'http://';
        if ($requestContext->getServerVar(ServerVars::HTTPS) === ServerVars::VALUE_HTTPS_ON) {
            $protocol = 'https://';
        }

        // get clean request path without query string etc...
        $requestPath = parse_url($requestContext->getServerVar(ServerVars::X_REQUEST_URI), PHP_URL_PATH);

        // init all rewrite mappers by types and do look up
        foreach ($rewriteMaps as $rewriteMapType => $rewriteMapParams) {
            // Include the requested hostname as a param, some mappers might need it
            $rewriteMapParams['headerHost'] = $request->getHeader(Protocol::HEADER_HOST);
            // Same for the protocol
            $rewriteMapParams['protocol'] = $protocol;

            // Get ourselves a rewriteMapper of the right type
            $rewriteMapper = new $rewriteMapType($rewriteMapParams);
            // lookup by request path
            if ($targetUrl = $rewriteMapper->lookup($requestPath)) {
                // set enhance uri to response
                $response->addHeader(Protocol::HEADER_LOCATION, $targetUrl);
                // send redirect status
                $response->setStatusCode(301);
                // add header to be sure that is was us
                $response->addHeader('X-Rewritten-By', __CLASS__);
                // set response state to be dispatched after this without calling other modules process
                $response->setState(HttpResponseStates::DISPATCH);

                // We found something, stop the loop
                break;
            }
        }
        return true;
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
