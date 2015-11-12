<?php

/**
 * \AppserverIo\WebServer\Modules\VirtualHostModule
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Bernhard Wick <bw@appserver.io>
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
use AppserverIo\Server\Exceptions\ServerException;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;

/**
 * Class VirtualHostModule
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class VirtualHostModule implements HttpModuleInterface
{

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'virtualHost';

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
     * Hold's the server context instance
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Initializes all server certs for certain virtualhosts defined in configuration
     *
     * @return void
     */
    protected function initCerts()
    {
        // get stream context to enhance it
        $streamContext = $this->getServerContext()->getStreamContext();
        // get logger
        $logger = $this->getServerContext()->getLogger();
        
        // set possible ssl certs for virtual hosts
        $virtualHosts = $this->getServerContext()->getServerConfig()->getVirtualHosts();
        
        foreach ($virtualHosts as $virtualHostName => $virtualHostData) {
            if (isset($virtualHostData['params']['certPath'])) {
                $certPath = $virtualHostData['params']['certPath'];
                // get real cert path
                $realCertPath = str_replace('/', DIRECTORY_SEPARATOR, $certPath);
                // check if relative or absolute path was given
                if (strpos($realCertPath, '/') === false) {
                    $realCertPath = SERVER_BASEDIR . $realCertPath;
                }
                // try to enhance sni server certs array
                try {
                    // add to sni server certs configuration
                    $streamContext->addSniServerCert($virtualHostName, $realCertPath);
                } catch (\Exception $e) {
                    // log exception message
                    $logger->error($e->getMessage());
                }
            }
        }
    }
    
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
        
        // init ssl certs based on virtual hosts via sni server cert feature
        $this->initCerts();
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
        // if false hook is comming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }

        // set req and res object internally
        $this->request = $request;
        $this->response = $response;

        $virtualHosts = $this->getServerContext()
            ->getServerConfig()
            ->getVirtualHosts();
        $serverName = $requestContext->getServerVar(ServerVars::SERVER_NAME);

        // check if current host matches any virtual host configuration
        if (isset($virtualHosts[$serverName])) {
            // read out params
            $params = $virtualHosts[$serverName]['params'];
            // iterate over all params and try to set as server var via mapping
            foreach ($params as $paramName => $paramValue) {
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

            // Add the headers we have (if any) to the configuration's headers pool
            if (! empty($virtualHosts[$serverName]['headers'])) {
                // Set the rewrites we encountered as a temporary module var
                $requestContext->setModuleVar(ModuleVars::VOLATILE_HEADERS, $virtualHosts[$serverName]['headers']);
            }
            
            // Add the rewrites we have (if any) to the configuration's rewrite pool
            if (! empty($virtualHosts[$serverName]['rewrites'])) {
                // Set the rewrites we encountered as a temporary module var
                $requestContext->setModuleVar(ModuleVars::VOLATILE_REWRITES, $virtualHosts[$serverName]['rewrites']);
            }

            // Add the environment vars we have (if any) to the configuration's environment variable pool
            if (! empty($virtualHosts[$serverName]['environmentVariables'])) {
                // Set the environment variables we encountered as a temporary module var
                $requestContext->setModuleVar(ModuleVars::VOLATILE_ENVIRONMENT_VARIABLES, $virtualHosts[$serverName]['environmentVariables']);
            }

            // Add the accesses (if any) to the configuration's access pool
            if (! empty($virtualHosts[$serverName]['accesses'])) {
                // Set the environment variables we encountered as a temporary module var
                $requestContext->setModuleVar(ModuleVars::VOLATILE_ACCESSES, $virtualHosts[$serverName]['accesses']);
            }

            // add the analytics (if any) to the configuration's analytics pool
            if (! empty($virtualHosts[$serverName]['analytics'])) {
                // set the analytics we encountered as a temporary module var
                $requestContext->setModuleVar(ModuleVars::VOLATILE_ANALYTICS, $virtualHosts[$serverName]['analytics']);
            }

            // Add the locations we have (if any) to the configuration's location pool
            if (! empty($virtualHosts[$serverName]['locations'])) {
                // Set the locations we encountered as a temporary module var
                $requestContext->setModuleVar(ModuleVars::VOLATILE_LOCATIONS, $virtualHosts[$serverName]['locations']);
            }

            // Add the rewriteMaps we have (if any) to the configuration's rewriteMaps pool
            if (! empty($virtualHosts[$serverName]['rewriteMaps'])) {
                // Set the rewriteMaps we encountered as a temporary module var
                $requestContext->setModuleVar(ModuleVars::VOLATILE_REWRITE_MAPS, $virtualHosts[$serverName]['rewriteMaps']);
            }

            // Add the authentications we have (if any) to the configuration's authentications pool
            if (! empty($virtualHosts[$serverName]['authentications'])) {
                // Set the authentications we encountered as a temporary module var
                $requestContext->setModuleVar(ModuleVars::VOLATILE_AUTHENTICATIONS, $virtualHosts[$serverName]['authentications']);
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
