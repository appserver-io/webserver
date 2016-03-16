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
use AppserverIo\Http\HttpProtocol;
use AppserverIo\Http\HttpException;
use AppserverIo\Http\HttpResponseStates;
use AppserverIo\Server\Sockets\StreamSocket;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Dictionaries\ModuleVars;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;

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
     * Defines the default transport
     *
     * @var string
     */
    const PROXY_DEFAULT_TRANSPORT = 'tcp';

    /**
     * Holds connection to backend
     *
     * @var StreamSocket
     */
    public $connection = null;

    /**
     * Flag if proxy should be disconnected
     *
     * @var boolean
     */
    public $shouldDisconnect = false;

    /**
     * Check if proxy connection should be disconnected
     *
     * @return void
     */
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
        // get server context to local ref
        $serverContext = $this->getServerContext();

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
            // init upstreamname and transport
            $upstreamName = null;
            $transport = 'tcp';

            // check if we've configured module variables
            if ($requestContext->hasModuleVar(ModuleVars::VOLATILE_FILE_HANDLER_VARIABLES)) {
                // load the volatile file handler variables and set connection data
                $fileHandlerVariables = $requestContext->getModuleVar(ModuleVars::VOLATILE_FILE_HANDLER_VARIABLES);
                // check if upstream is set for proxy function
                if (isset($fileHandlerVariables['upstream'])) {
                    $upstreamName =  $fileHandlerVariables['upstream'];
                }
                if (isset($fileHandlerVariables['transport'])) {
                    $transport = $fileHandlerVariables['transport'];
                }
            }

            // if there was no upstream defined
            if (is_null($upstreamName)) {
                throw new ModuleException('No upstream configured for proxy filehandler');
            }

            // get upstream instance by configured upstream name
            $upstream = $serverContext->getUpstream($upstreamName);

            // find next proxy server by given upstream type
            $remoteAddr = $requestContext->getServerVar(ServerVars::REMOTE_ADDR);
            $proxyServer = $upstream->findServer(md5($remoteAddr));

            // build proxy socket address for connection
            $proxySocketAddress = sprintf('%s://%s:%s', $transport, $proxyServer->getAddress(), $proxyServer->getPort());

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
                $this->connection = StreamSocket::getClientInstance($proxySocketAddress);
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

            // populate request headers
            $headers = $request->getHeaders();
            foreach ($headers as $headerName => $headerValue) {
                // @todo: make keep-alive available for proxy connections
                if ($headerName === HttpProtocol::HEADER_CONNECTION) {
                    $headerValue = HttpProtocol::HEADER_CONNECTION_VALUE_CLOSE;
                }
                $rawRequestString .= $headerName . HttpProtocol::HEADER_SEPARATOR . $headerValue . "\r\n";
            }

            // get current protocol
            $reqProto = $requestContext->getServerVar(ServerVars::REQUEST_SCHEME);

            // add proxy depending headers
            $rawRequestString .= HttpProtocol::HEADER_X_FORWARD_FOR . HttpProtocol::HEADER_SEPARATOR . $remoteAddr . "\r\n";
            $rawRequestString .= HttpProtocol::HEADER_X_FORWARDED_PROTO . HttpProtocol::HEADER_SEPARATOR . $reqProto . "\r\n";
            $rawRequestString .= "\r\n";

            // write headers to proxy connection
            $connection->write($rawRequestString);

            // copy raw request body stream to proxy connection
            $connection->copyStream($request->getBodyStream());

            // read status line from proxy connection
            $statusLine = $connection->readLine(1024, 5);
            // parse start line
            list(, $responseStatusCode) = explode(' ', $statusLine);

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

                // check header name for server
                // @todo: make this configurable
                if ($headerName === HttpProtocol::HEADER_SERVER) {
                    continue;
                }

                // add header
                $response->addHeader(trim($headerName), trim($headerValue));
            }

            // set flag false by default
            $this->shouldDisconnect = false;

            // check if connection should be closed as given in connection header
            if ($response->getHeader(HttpProtocol::HEADER_CONNECTION) === HttpProtocol::HEADER_CONNECTION_VALUE_CLOSE) {
                $this->shouldDisconnect = true;
            }

        } catch (\AppserverIo\Psr\Socket\SocketReadException $e) {
            // close and unset connection and try to process the request again to
            // not let a white page get delivered to the client
            $this->shouldDisconnect = true;
            return $this->process($request, $response, $requestContext, $hook);

        } catch (\AppserverIo\Psr\Socket\SocketReadTimeoutException $e) {
            // close and unset connection and try to process the request again to
            // not let a white page get delivered to the client
            $this->shouldDisconnect = true;
            return $this->process($request, $response, $requestContext, $hook);
        }

        // set response to be dispatched at this point
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

    /**
     * Return the server's context
     *
     * @return ServerContextInterface
     */
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
