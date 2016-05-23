<?php

/**
 * \AppserverIo\WebServer\Modules\AccessModule
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
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Dictionaries\ModuleVars;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;

/**
 * Class AccessModule
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class AccessModule implements HttpModuleInterface
{

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'access';

    /**
     * Holds the server context instance
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Holds an array of all accesses
     *
     * @var array
     */
    protected $accesses;

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
     * Returns the server context instance
     *
     * @return \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }

    /**
     * Initiates the module
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return boolean
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
        $this->serverContext = $serverContext;
        $this->accesses = $serverContext->getServerConfig()->getAccesses();
    }

    /**
     * Implements module logic for given hook
     *
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface          $request        A request object
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface         $response       A response object
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     * @param integer                                                $hook           The current hook to process logic for
     *
     * @return boolean
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

        // get default access definitions
        $accesses = $this->accesses;

        // check if there are some volatile access definitions so use them and override global accesses
        if ($requestContext->hasModuleVar(ModuleVars::VOLATILE_ACCESSES)) {
            // reset by volatile accesses
            $accesses = $requestContext->getModuleVar(ModuleVars::VOLATILE_ACCESSES);
        }

        // generally everything is not allowed
        $allowed = false;

        if (isset($accesses['allow'])) {
            // check allow accesses information if something matches
            foreach ($accesses['allow'] as $accessData) {
                // we are optimistic an initial say data will match
                $matchAllow = true;

                // check if accessData matches server vars
                foreach ($accessData as $serverVar => $varPattern) {
                    // check if server var exists
                    if ($requestContext->hasServerVar($serverVar)) {
                        // check if pattern matches
                        if (! preg_match('/' . $varPattern . '/', $requestContext->getServerVar($serverVar))) {
                            $matchAllow = false;
                            // break here if anything not matches
                            break;
                        }
                    }
                }

                if ($matchAllow) {
                    // set allowed flag true
                    $allowed = true;
                    // break here cause' we found an allowed access
                    break;
                }
            }
        }

        if (isset($accesses['deny'])) {
            // check deny accesses information if something matches
            foreach ($accesses['deny'] as $accessData) {
                // initial nothing denies the request
                $matchDeny = false;

                // check if accessData matches server vars
                foreach ($accessData as $serverVar => $varPattern) {
                    // check if server var exists
                    if ($requestContext->hasServerVar($serverVar)) {
                        // check if pattern matches
                        if (preg_match('/' . $varPattern . '/', $requestContext->getServerVar($serverVar))) {
                            $matchDeny = true;
                            // break here if anything matches
                            break;
                        }
                    }
                }

                if ($matchDeny) {
                    // set allowed flag false
                    $allowed = false;
                    // break here cause' we found an allowed access
                    break;
                }
            }
        }

        // check if it's finally not allowed
        if (! $allowed) {
            throw new ModuleException('This request is forbidden', 403);
        }
    }

    /**
     * Returns an array of module names which should be executed first
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
     * @return boolean
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function prepare()
    {
        // nothing to prepare for this module
    }
}
