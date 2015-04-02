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
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Dictionaries\ServerVars;
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
    protected $connection;
    
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
        // if wrong hook is coming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }

        
        try {

            // check if connection object was initialised but connection resource is not ready
            if ($this->connection) {
                
                var_dump($this->connection->getConnectionResource());
                var_dump(stream_get_meta_data($this->connection->getConnectionResource()));
                
                if (!is_resource($this->connection->getConnectionResource())) {
                    // unset connection instance to for a new one to be created
                    unset($this->connection);
                }
            }

            // check if no connection instance is there
            if (!$this->connection) {
                // create and connect to defined backend
                echo '#### CONNECT TO BACKEND #### in Thread ' . \Thread::getCurrentThreadId() . PHP_EOL;
                $this->connection = StreamSocket::getClientInstance('tcp://127.0.0.1:80');
                $response->setBodyStream($this->connection->getConnectionResource());
            }

            $connection = $this->connection;

            $rawRequestString = sprintf('%s %s %s' . "\r\n", $request->getMethod(), $request->getUri(), HttpProtocol::VERSION_1_1 );
            $headers = $request->getHeaders();
    
            foreach ($headers as $headerName => $headerValue) {
                $rawRequestString .= $headerName . HttpProtocol::HEADER_SEPARATOR . $headerValue . "\r\n";
            }
    
            $rawRequestString .= "\r\n";
            
            var_dump($connection->write($rawRequestString));
            
            var_dump(socket_get_status($connection->getConnectionResource()));
            
            usleep(1000);
            
            $statusLine = $connection->readLine(1024, 5);
            
            list($httpVersion, $responseStatusCode) = explode(' ', $statusLine);
            
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
            
            // check if connection should be closed
            if ($response->getHeader(HttpProtocol::HEADER_CONNECTION) === HttpProtocol::HEADER_CONNECTION_VALUE_CLOSE) {
                $connection->close();
                unset($connection);
                
                echo "CLOSE CONNECTION########################" . PHP_EOL;
            }
            
        } catch(\Exception $e) {
            
            echo '#### EXCEPTION #### in Thread ' . \Thread::getCurrentThreadId() . PHP_EOL;
            
            echo $e;
            // reset connection
            unset($this->connection);
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
        echo __METHOD__ . PHP_EOL;
        $this->serverContext = $serverContext;
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
    
    public function __destruct()
    {
        echo __METHOD__ . PHP_EOL;
    }

}