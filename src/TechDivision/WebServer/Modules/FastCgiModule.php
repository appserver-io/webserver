<?php

/**
 * \TechDivision\WebServer\Modules\FastCgiModule
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
 * @subpackage Modules
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 * @link       https://github.com/ebernhardson/fastcgi
 */

namespace TechDivision\WebServer\Modules;

use EBernhardson\FastCGI\Client;
use EBernhardson\FastCGI\CommunicationException;
use TechDivision\Http\HttpProtocol;
use TechDivision\Http\HttpResponseStates;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\WebServer\Dictionaries\ServerVars;
use TechDivision\WebServer\Dictionaries\ModuleVars;
use TechDivision\WebServer\Dictionaries\ModuleHooks;
use TechDivision\WebServer\Interfaces\ModuleInterface;
use TechDivision\WebServer\Exceptions\ModuleException;
use TechDivision\WebServer\Interfaces\ServerContextInterface;

/**
 * This module allows us to let requests be handled by Fast-CGI client 
 * that has been configured in the web servers configuration. 
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 * @link       https://github.com/ebernhardson/fastcgi
 */
class FastCgiModule extends Client implements ModuleInterface
{
    
    /**
     * Defines the module name.
     *
     * @var string
     */
    const MODULE_NAME = 'fastcgi';

    /**
     * Holds the servers context instance.
     *
     * @var \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    protected $serverContext;
    
    /**
     * Overwrite default constructor and initialize the module
     * with the default values for a Fast-CGI connection.
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct('127.0.0.1', 9000);
    }
    
    /**
     * Implements module logic for given hook, in this case passing the Fast-CGI request
     * through to the configured Fast-CGI server.
     *
     * @param \TechDivision\Http\HttpRequestInterface  $request  The request object
     * @param \TechDivision\Http\HttpResponseInterface $response The response object
     * @param int                                      $hook     The current hook to process logic for
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function process(HttpRequestInterface $request, HttpResponseInterface $response, $hook)
    {
        // if false hook is comming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }
        
        // make the server context locally available
        $serverContext = $this->getServerContext();

        // check if server handler sais php modules should react on this request as file handler
        if ($serverContext->getServerVar(ServerVars::SERVER_HANDLER) !== self::MODULE_NAME) {
            return;
        }

        // check if file does not exist
        if (!$serverContext->hasServerVar(ServerVars::SCRIPT_FILENAME)) {
            $response->setStatusCode(404);
            throw new ModuleException(null, 404);
        }

        try {
            
            // set the connection data to be used for the Fast-CGI connection
            if ($serverContext->hasModuleVar(ModuleVars::VOLATILE_FILE_HANDLER_VARIABLES)) {
                // load the volatile file handler variables and set connection data
                $fileHandlerVariables = $serverContext->getModuleVar(ModuleVars::VOLATILE_FILE_HANDLER_VARIABLES);
                if (isset($fileHandlerVariables['host'])) {
                    $this->host = $fileHandlerVariables['host'];
                }
                if (isset($fileHandlerVariables['port'])) {
                    $this->port = $fileHandlerVariables['port'];
                }
            }
            
            // prepare the Fast-CGI environment variables
            $environment = array(
                ServerVars::GATEWAY_INTERFACE => 'FastCGI/1.0',
                ServerVars::REQUEST_METHOD    => $serverContext->getServerVar(ServerVars::REQUEST_METHOD),
                ServerVars::SCRIPT_FILENAME   => $serverContext->getServerVar(ServerVars::SCRIPT_FILENAME),
                ServerVars::QUERY_STRING      => $serverContext->getServerVar(ServerVars::QUERY_STRING),
                ServerVars::SCRIPT_NAME       => $serverContext->getServerVar(ServerVars::SCRIPT_NAME),
                ServerVars::REQUEST_URI       => $serverContext->getServerVar(ServerVars::REQUEST_URI),
                ServerVars::DOCUMENT_ROOT     => $serverContext->getServerVar(ServerVars::DOCUMENT_ROOT),
                ServerVars::SERVER_PROTOCOL   => $serverContext->getServerVar(ServerVars::SERVER_PROTOCOL),
                ServerVars::HTTPS             => $serverContext->getServerVar(ServerVars::HTTPS),
                ServerVars::SERVER_SOFTWARE   => $serverContext->getServerVar(ServerVars::SERVER_SOFTWARE),
                ServerVars::REMOTE_ADDR       => $serverContext->getServerVar(ServerVars::REMOTE_ADDR),
                ServerVars::REMOTE_PORT       => $serverContext->getServerVar(ServerVars::REMOTE_PORT),
                ServerVars::SERVER_ADDR       => $serverContext->getServerVar(ServerVars::SERVER_ADDR),
                ServerVars::SERVER_PORT       => $serverContext->getServerVar(ServerVars::SERVER_PORT),
                ServerVars::SERVER_NAME       => $serverContext->getServerVar(ServerVars::SERVER_NAME)
            );
            
            // if we found a redirect status, add it to the environment variables
            if ($serverContext->hasServerVar(ServerVars::REDIRECT_STATUS)) {
                $environment[ServerVars::REDIRECT_STATUS] = $serverContext->getServerVar(ServerVars::REDIRECT_STATUS);
            }
            
            // if we found a Content-Type header, add it to the environment variables
            if ($request->hasHeader(HttpProtocol::HEADER_CONTENT_TYPE)) {
                $environment['CONTENT_TYPE'] = $request->getHeader(HttpProtocol::HEADER_CONTENT_TYPE);
            }
            
            // if we found a Content-Length header, add it to the environment variables
            if ($request->hasHeader(HttpProtocol::HEADER_CONTENT_LENGTH)) {
                $environment['CONTENT_LENGTH'] = $request->getHeader(HttpProtocol::HEADER_CONTENT_LENGTH);
            }
            
            // create an HTTP_ environment variable for each header
            foreach ($request->getHeaders() as $key => $value) {
                $environment['HTTP_' . str_replace('-', '_', strtoupper($key))] = $value;
            }
            
            // create a new Fast-CGI connection and process the request
            if ($request->hasHeader(HttpProtocol::HEADER_CONTENT_LENGTH)) {
                $this->request($environment, $bodyContent = $request->getBodyContent());
            } else {
                $this->request($environment, '');
            }
            
            // process the response
            $fastCgiResponse = $this->response();
            
            // close the Fast-CGI connection
            $this->close();
            
            // set the Fast-CGI response value in the WebServer response
            $response->setStatusCode($fastCgiResponse['statusCode']);
            $response->appendBodyStream($fastCgiResponse['body']);

            // set the headers found in the Fast-CGI response
            if (array_key_exists('headers', $fastCgiResponse)) {
                foreach ($fastCgiResponse['headers'] as $headerName => $headerValue) {
                    // if found an array, e. g. for the Set-Cookie header, we add each value
                    if (is_array($headerValue)) {
                        foreach ($headerValue as $value) {
                            $response->addHeader($headerName, $value, true);
                        }
                    } else {
                        $response->addHeader($headerName, $headerValue);
                    }
                }
            }

            // add the X-Powered-By header
            $response->addHeader(HttpProtocol::HEADER_X_POWERED_BY, __CLASS__);

            // set response state to be dispatched after this without calling other modules process
            $response->setState(HttpResponseStates::DISPATCH);
        
        } catch (\Exception $e) { // catch all exceptions
            throw new ModuleException($e);
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
     * Initiates the module
     *
     * @param \TechDivision\WebServer\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
        $this->serverContext = $serverContext;
    }

    /**
     * Return's the server context instance
     *
     * @return \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }
}
