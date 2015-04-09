<?php

/**
 * \AppserverIo\WebServer\Modules\ProxyModule
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
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Dictionaries\ModuleVars;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Server\Sockets\StreamSocket;
use AppserverIo\Http\HttpRequest;
use AppserverIo\Http\HttpProtocol;
use AppserverIo\Psr\HttpMessage\StreamInterface;

/**
 * Class ProxyModule
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class ProxyModule implements HttpModuleInterface
{
    /**
     * Defines the module's name
     * 
     * @var string
     */
    const MODULE_NAME = 'proxy';
    
    /**
     * Holds connection to backend
     * 
     * @var StreamSocket
     */
    public $connection = null;
    
    public $shouldDisconnect = false;
    
    public function checkShouldDisconnect()
    {
        // check if we should reconnection connection next time
        if ($this->shouldDisconnect === true) {
            // close connection first if exists
            if ($this->connection) {
                $this->connection->close();
                $this->connection = null;
            }
            $this->shouldDisconnect = false;
        }
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
        $serverContext = $this->getServerContext();
        
        $upstream = $serverContext->getUpstream('backend');
        
        // check if we've configured module variables
        if ($requestContext->hasModuleVar(ModuleVars::VOLATILE_FILE_HANDLER_VARIABLES)) {
            // load the volatile file handler variables and set connection data
            $fileHandlerVariables = $requestContext->getModuleVar(ModuleVars::VOLATILE_FILE_HANDLER_VARIABLES);
        }
        
        // check if response post is is comming
        if (ModuleHooks::RESPONSE_POST === $hook) {
            $this->checkShouldDisconnect();
            return;
        }
        
        // if wrong hook is coming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }

        try {
            // check if should reconnect
            $this->checkShouldDisconnect();
            
            // check if proxy connection object was initialised but connection resource is not ready
            if (($this->connection) && ($this->connection->getStatus() === false)) {
                // unset connection if corrupt
                $this->connection = null;
            }

            // check if connection should be established
            if ($this->connection === null) {
                // create and connect to defined backend
                $this->connection = StreamSocket::getClientInstance('tcp://127.0.0.1:80');
                // set proxy connection resource as stream source for body stream directly
                // that avoids huge memory consumtion when transferring big files via proxy connections
                $response->setBodyStream($this->connection->getConnectionResource());
            }

            // get connection to local var
            $connection = $this->connection;

            // build up raw request start line
            $rawRequestString = sprintf(
                '%s %s %s' . "\r\n", 
                $request->getMethod(),
                $request->getUri(),
                HttpProtocol::VERSION_1_1
            );
            $headers = $request->getHeaders();
            foreach ($headers as $headerName => $headerValue) {
                $rawRequestString .= $headerName . HttpProtocol::HEADER_SEPARATOR . $headerValue . "\r\n";
            }
            $rawRequestString .= "\r\n";
            
            // write headers to proxy connection
            $connection->write($rawRequestString);
            // copy raw request body stream to proxy connection
            $connection->copyStream($request->getBodyStream());
            
            // read status line from proxy connection
            $statusLine = $connection->readLine(1024, 5);
            // parse start line
            list($httpVersion, $responseStatusCode) = explode(' ', $statusLine);
            
            // map everything from proxy response to our response object
            $response->setStatusCode($responseStatusCode);
            
            $line = '';
            $messageHeaders = '';
            while (!in_array($line, array("\r\n", "\n"))) {
                // read next line
                $line = $connection->readLine();
                // enhance headers
                $messageHeaders .= $line;
            }
                    
            // remove ending CRLF's before parsing
            $messageHeaders = trim($messageHeaders);
            // check if headers are empty
            if (strlen($messageHeaders) === 0) {
                throw new HttpException('Missing headers');
            }
            
            // delimit headers by CRLF
            $headerLines = explode("\r\n", $messageHeaders);

            // iterate all headers
            foreach ($headerLines as $headerLine) {
                // extract header info
                $extractedHeaderInfo = explode(HttpProtocol::HEADER_SEPARATOR, trim($headerLine));
                if ((!$extractedHeaderInfo) || ($extractedHeaderInfo[0] === $headerLine)) {
                    throw new HttpException('Wrong header format');
                }
                // split name and value
                list($headerName, $headerValue) = $extractedHeaderInfo;
                // add header
                $response->addHeader(trim($headerName), trim($headerValue));
            }
            
            // set flag false by default
            $this->shouldDisconnect = false;
            
            // check if connection should be closed as given in connection header
            if ($response->getHeader(HttpProtocol::HEADER_CONNECTION) === HttpProtocol::HEADER_CONNECTION_VALUE_CLOSE) {
                $this->shouldDisconnect = true;
            }

        } catch(\AppserverIo\Psr\Socket\SocketReadException $e) {
            // close and unset connection and try to process the request again to
            // not let a white page get delivered to the client
            $this->shouldDisconnect = true;
            return $this->process($request, $response, $requestContext, $hook);
            
        } catch(\Exception $e) {
            $this->shouldDisconnect = true;
        }
        
        $response->setState(HttpResponseStates::DISPATCH);
    }
    
    /**
     * Return an array of module names which should be executed first
     *
     * @return array The array of module names
     */
    public function getDependencies()
    {
        return [];
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
        $this->serverContext = $serverContext;
    }
    
    public function getServerContext()
    {
        return $this->serverContext;
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
