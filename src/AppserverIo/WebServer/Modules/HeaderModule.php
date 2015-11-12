<?php

/**
 * \AppserverIo\WebServer\Modules\HeaderModule
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
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Dictionaries\ModuleVars;

/**
 * Class HeaderModule
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class HeaderModule implements HttpModuleInterface
{

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'header';

    /**
     * Holds the server context instance
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Returns the request instance
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
     * Applies the given headers to response
     *
     * @param array  $headers an array of headers to apply
     * @param string $type    the type of headers to apply
     *
     * @return @void
     */
    protected function applyHeaders(array $headers, $type = "response")
    {
        // return if we do not have any headers
        if (!isset($headers[$type])) {
            return;
        }

        foreach ($headers[$type] as $header) {
            // check if override flag was set for existing header
            if (($this->getResponse()->hasHeader($header['name']))
            && ($header['override'] !== true)
            && ($header['append'] !== true)) {
                // do nothing in this case and go on with next header
                continue;
            }
            // add header to response
            $this->getResponse()->addHeader($header['name'], $header['value'], $header['append']);
        }
    }

    /**
     * Implement's module logic for given hook
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
        // if false hook is comming do nothing
        if (ModuleHooks::RESPONSE_PRE !== $hook) {
            return;
        }

        // set req and res object internally
        $this->request = $request;
        $this->response = $response;

        // get server context ref to local func
        $serverContext = $this->getServerContext();

        // init volatile headers array
        $volatileHeaders = array();

        // apply possible volatile headers
        if ($requestContext->hasModuleVar(ModuleVars::VOLATILE_HEADERS)) {
            $volatileHeaders = $requestContext->getModuleVar(ModuleVars::VOLATILE_HEADERS);
        }
        // apply server headers first and then volatile headers
        $this->applyHeaders(
            array_merge_recursive(
                $serverContext->getServerConfig()->getHeaders(),
                $volatileHeaders
            )
        );

        // signal good processing state
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
