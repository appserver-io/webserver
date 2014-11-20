<?php

/**
 * \AppserverIo\WebServer\Modules\AuthenticationModule
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */

namespace AppserverIo\WebServer\Modules;

use AppserverIo\Connection\ConnectionRequestInterface;
use AppserverIo\Connection\ConnectionResponseInterface;
use AppserverIo\Http\HttpProtocol;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Dictionaries\ModuleVars;
use AppserverIo\Server\Interfaces\ModuleInterface;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Http\HttpRequestInterface;
use AppserverIo\Http\HttpResponseInterface;
use AppserverIo\WebServer\Interfaces\AuthenticationInterface;
use AppserverIo\WebServer\Authentication\BasicAuthentication;

/**
 * Class AuthenticationModule
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
class AuthenticationModule implements ModuleInterface
{
    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'authentication';

    /**
     * Hold's the server context instance
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Hold's all authentication instances
     *
     * @var array
     */
    protected $authentications;

    /**
     * Hold's all authenticationType instances used within server while running
     *
     * @var array
     */
    protected $typeInstances;

    /**
     * Hold's an array of all uri patterns to match uri for
     *
     * @var array
     */
    protected $uriPatterns;

    /**
     * Return's the request instance
     *
     * @return \AppserverIo\Http\HttpRequestInterface The request instance
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the response instance
     *
     * @return \AppserverIo\Http\HttpResponseInterface The response instance;
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
        $this->authentications = $serverContext->getServerConfig()->getAuthentications();

        // check if authentication are given
        if (is_array($this->authentications)) {
            // init all uri patterns
            foreach ($this->authentications as $uri => $params) {
                $this->uriPatterns[$uri] = $params;
                // pre init types by calling getter in init
                $this->getAuthenticationTypeInstance($params["type"], $params);
            }
        }
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
     * Return's pre initiated auth type instance by given type
     *
     * @param string $type The authentication type
     * @param string $data The data got from client for authentication process
     *
     * @return \AppserverIo\Server\Interfaces\AuthenticationInterface
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function getAuthenticationTypeInstance($type, array $data = array())
    {
        if (!is_object($this->typeInstances[$type])) {
            // check if type class does not exist
            if (!class_exists($type)) {
                throw new ModuleException("No auth type found for '$type'", 500);
            }
            // init type by given class definition and data
            $this->typeInstances[$type] = new $type();
        }
        return $this->typeInstances[$type];
    }

    /**
     * Implement's module logic for given hook
     *
     * @param \AppserverIo\Connection\ConnectionRequestInterface     $request        A request object
     * @param \AppserverIo\Connection\ConnectionResponseInterface    $response       A response object
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     * @param int                                                     $hook           The current hook to process logic for
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function process(
        ConnectionRequestInterface $request,
        ConnectionResponseInterface $response,
        RequestContextInterface $requestContext,
        $hook
    ) {
        // In php an interface is, by definition, a fixed contract. It is immutable.
        // So we have to declair the right ones afterwards...
        /** @var $request \AppserverIo\Http\HttpRequestInterface */
        /** @var $response \AppserverIo\Http\HttpResponseInterface */

        // if false hook is coming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }

        // set req and res object internally
        $this->request = $request;
        $this->response = $response;

        // Get teh authentications locally so we do not mess with inter-request configuration
        $authentications = $this->authentications;

        // check if there are some volatile rewrite map definitions so add them
        if ($requestContext->hasModuleVar(ModuleVars::VOLATILE_AUTHENTICATIONS)) {
            $volatileAuthentications = $requestContext->getModuleVar(ModuleVars::VOLATILE_AUTHENTICATIONS);
            // merge rewrite maps
            $authentications = array_merge(
                $volatileAuthentications,
                $authentications
            );
        }

        // check authentication information if something matches
        foreach ($authentications as $uriPattern => $data) {
            // check if pattern matches uri
            if (preg_match(
                '/' . $uriPattern . '/',
                $requestContext->getServerVar(ServerVars::X_REQUEST_URI)
            )
            ) {
                // set type Instance to local ref
                $typeInstance = $this->getAuthenticationTypeInstance($data["type"]);
                // check if client sends an authentication header
                if ($authHeader = $request->getHeader(HttpProtocol::HEADER_AUTHORIZATION)) {
                    // init type instance by auth header
                    $typeInstance->init($authHeader);
                    // check if auth works
                    if ($typeInstance->auth($data)) {
                        // set server vars
                        $requestContext->setServerVar(ServerVars::REMOTE_USER, $typeInstance->getUsername());
                        // break out because everything is fine at this point
                        return true;
                    }
                }
                // send header for challenge authentication against client
                $response->addHeader(
                    HttpProtocol::HEADER_WWW_AUTHENTICATE,
                    $typeInstance->getType() . ' realm="' . $data["realm"] . "'"
                );
                // throw exception for auth required
                throw new ModuleException(null, 401);
            }
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
