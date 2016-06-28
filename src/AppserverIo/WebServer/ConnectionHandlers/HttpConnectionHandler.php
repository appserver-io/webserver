<?php

/**
 * \AppserverIo\WebServer\ConnectionHandlers\HttpConnectionHandler
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
 * @link      http://www.appserver.io
 */

namespace AppserverIo\WebServer\ConnectionHandlers;

use AppserverIo\Server\Dictionaries\EnvVars;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Interfaces\ConnectionHandlerInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\WorkerInterface;
use AppserverIo\Psr\Socket\SocketInterface;
use AppserverIo\Psr\Socket\SocketReadException;
use AppserverIo\Psr\Socket\SocketReadTimeoutException;
use AppserverIo\Psr\Socket\SocketServerException;
use AppserverIo\Psr\HttpMessage\Protocol;
use AppserverIo\Http\HttpRequest;
use AppserverIo\Http\HttpResponse;
use AppserverIo\Http\HttpPart;
use AppserverIo\Http\HttpProtocol;
use AppserverIo\Http\HttpQueryParser;
use AppserverIo\Http\HttpRequestParser;
use AppserverIo\Http\HttpResponseStates;

/**
 * Class HttpConnectionHandler
 *
 * @author Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link https://github.com/appserver-io/webserver
 * @link http://www.appserver.io
 */
class HttpConnectionHandler implements ConnectionHandlerInterface
{

    /**
     * Defines the read length for http connections
     *
     * @var int
     */
    const HTTP_CONNECTION_READ_LENGTH = 2048;

    /**
     * Holds parser instance
     *
     * @var \AppserverIo\Http\HttpRequestParserInterface
     */
    protected $parser;

    /**
     * Holds the server context instance
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Holds the request's context instance
     *
     * @var \AppserverIo\Server\Interfaces\RequestContextInterface
     */
    protected $requestContext;

    /**
     * Holds an array of modules to use for connection handler
     *
     * @var array
     */
    protected $modules;

    /**
     * Holds errors page template
     *
     * @var string
     */
    protected $errorsPageTemplate;

    /**
     * Holds the connection instance
     *
     * @var \AppserverIo\Psr\Socket\SocketInterface
     */
    protected $connection;

    /**
     * Holds the worker instance
     *
     * @var \AppserverIo\Server\Interfaces\WorkerInterface
     */
    protected $worker;

    /**
     * Flag if a shutdown function was registered or not
     *
     * @var boolean
     */
    protected $hasRegisteredShutdown = false;

    /**
     * Inits the connection handler by given context and params
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The server's context
     * @param array                                                 $params        The params for connection handler
     *
     * @return void
     */
    public function init(ServerContextInterface $serverContext, array $params = null)
    {
        // set server context
        $this->serverContext = $serverContext;

        // set params
        $this->errorsPageTemplate = $params["errorsPageTemplate"];

        // init http request object
        $httpRequest = new HttpRequest();

        // init http response object
        $httpResponse = new HttpResponse();
        // set default response headers
        $httpResponse->setDefaultHeaders(array(
            Protocol::HEADER_SERVER                 => $this->getServerConfig()->getSoftware(),
            Protocol::HEADER_CONNECTION             => Protocol::HEADER_CONNECTION_VALUE_CLOSE,
            Protocol::HEADER_X_FRAME_OPTIONS        => Protocol::HEADER_X_FRAME_OPTIONS_VALUE_DENY,
            Protocol::HEADER_X_XSS_PROTECTION       => Protocol::HEADER_X_XSS_PROTECTION_VALUE_ON,
            Protocol::HEADER_X_CONTENT_TYPE_OPTIONS => Protocol::HEADER_X_CONTENT_TYPE_OPTIONS_VALUE_NOSNIFF
        ));

        // setup http parser
        $this->parser = new HttpRequestParser($httpRequest, $httpResponse);
        $this->parser->injectQueryParser(new HttpQueryParser());
        $this->parser->injectPart(new HttpPart());

        // setup request context

        // get request context type
        $requestContextType = $this->getServerConfig()->getRequestContextType();

        /**
         * @var \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext
         */
        // instantiate and init request context
        $this->requestContext = new $requestContextType();
        $this->requestContext->init($this->getServerConfig());
    }

    /**
     * Injects all needed modules for connection handler to process
     *
     * @param array $modules An array of Modules
     *
     * @return void
     */
    public function injectModules($modules)
    {
        $this->modules = $modules;
    }

    /**
     * Returns all needed modules as array for connection handler to process
     *
     * @return array An array of Modules
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * Returns a specific module instance by given name
     *
     * @param string $name The modules name to return an instance for
     *
     * @return \AppserverIo\WebServer\Interfaces\HttpModuleInterface|null
     */
    public function getModule($name)
    {
        if (isset($this->modules[$name])) {
            return $this->modules[$name];
        }
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
     * Returns the request's context instance
     *
     * @return \AppserverIo\Server\Interfaces\RequestContextInterface
     */
    public function getRequestContext()
    {
        return $this->requestContext;
    }

    /**
     * Returns the server's configuration
     *
     * @return \AppserverIo\Server\Interfaces\ServerConfigurationInterface
     */
    public function getServerConfig()
    {
        return $this->getServerContext()->getServerConfig();
    }

    /**
     * Returns the parser instance
     *
     * @return \AppserverIo\Http\HttpRequestParserInterface
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Returns the connection used to handle with
     *
     * @return \AppserverIo\Psr\Socket\SocketInterface
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * Returns the worker instance which starte this worker thread
     *
     * @return \AppserverIo\Server\Interfaces\WorkerInterface
     */
    protected function getWorker()
    {
        return $this->worker;
    }

    /**
     * Returns the template for errors page to render
     *
     * @return string
     */
    public function getErrorsPageTemplate()
    {
        return $this->errorsPageTemplate;
    }

    /**
     * Handles the connection with the connected client in a proper way the given
     * protocol type and version expects for example.
     *
     * @param \AppserverIo\Psr\Socket\SocketInterface        $connection The connection to handle
     * @param \AppserverIo\Server\Interfaces\WorkerInterface $worker     The worker how started this handle
     *
     * @return bool Weather it was responsible to handle the firstLine or not.
     * @throws \Exception
     */
    public function handle(SocketInterface $connection, WorkerInterface $worker)
    {
        // register shutdown handler once to avoid strange memory consumption problems
        $this->registerShutdown();

        // add connection ref to self
        $this->connection = $connection;
        $this->worker = $worker;

        $serverConfig = $this->getServerConfig();

        // get instances for short calls
        $requestContext = $this->getRequestContext();

        $parser = $this->getParser();

        // Get our query parser
        $queryParser = $parser->getQueryParser();

        // Get the request and response
        $request = $parser->getRequest();
        $response = $parser->getResponse();

        // init keep alive settings
        $keepAliveTimeout = (int) $serverConfig->getKeepAliveTimeout();
        $keepAliveMax = (int) $serverConfig->getKeepAliveMax();

        // init keep alive connection flag
        $keepAliveConnection = false;

        // init the request parser
        $parser->init();

        do {
            // try to handle request if its a http request
            try {
                // reset connection info to server vars
                $requestContext->setServerVar(ServerVars::REMOTE_ADDR, $connection->getAddress());
                $requestContext->setServerVar(ServerVars::REMOTE_PORT, $connection->getPort());

                // start time measurement for keep-alive timeout
                $keepaliveStartTime = microtime(true);

                // time settings
                $requestContext->setServerVar(ServerVars::REQUEST_TIME, time());

                /**
                 * Todo: maybe later on there have to be other time vars too especially for rewrite module.
                 *
                 * REQUEST_TIME_FLOAT
                 * TIME_YEAR
                 * TIME_MON
                 * TIME_DAY
                 * TIME_HOUR
                 * TIME_MIN
                 * TIME_SEC
                 * TIME_WDAY
                 * TIME
                 */

                // process modules by hook REQUEST_PRE
                $this->processModules(ModuleHooks::REQUEST_PRE);

                // init keep alive connection flag
                $keepAliveConnection = false;

                // set first line from connection
                $line = $connection->readLine(self::HTTP_CONNECTION_READ_LENGTH, $keepAliveTimeout);

                /**
                 * In the interest of robustness, servers SHOULD ignore any empty
                 * line(s) received where a Request-Line is expected.
                 * In other words, if
                 * the server is reading the protocol stream at the beginning of a
                 * message and receives a CRLF first, it should ignore the CRLF.
                 *
                 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.1
                 */
                if (in_array($line, array("\r\n", "\n"))) {
                    // ignore the first CRLF and go on reading the expected start-line.
                    $line = $connection->readLine(self::HTTP_CONNECTION_READ_LENGTH);
                }

                // parse read line
                $parser->parseStartLine($line);

                /**
                 * Parse headers in a proper way
                 *
                 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
                 */
                $messageHeaders = '';
                while (!in_array($line, array("\r\n", "\n"))) {
                    // read next line
                    $line = $connection->readLine();
                    // enhance headers
                    $messageHeaders .= $line;
                }

                // parse headers
                $parser->parseHeaders($messageHeaders);

                // process connection type keep-alive
                if (strcasecmp($request->getHeader(Protocol::HEADER_CONNECTION), Protocol::HEADER_CONNECTION_VALUE_KEEPALIVE) === 0) {
                    // calculate keep-alive idle time for comparison with keep-alive timeout
                    $keepAliveIdleTime = microtime(true) - $keepaliveStartTime;
                    // only if max connections or keep-alive timeout not reached yet
                    if (($keepAliveMax > 0) && ($keepAliveIdleTime < $keepAliveTimeout)) {
                        // enable keep alive connection
                        $keepAliveConnection = true;
                        // set keep-alive headers
                        $response->addHeader(Protocol::HEADER_CONNECTION, Protocol::HEADER_CONNECTION_VALUE_KEEPALIVE);
                        $response->addHeader(Protocol::HEADER_KEEP_ALIVE, "timeout: $keepAliveTimeout, max: $keepAliveMax");
                        // decrease keep-alive max
                        -- $keepAliveMax;
                    }
                }

                // check if message body will be transmitted
                if ($request->hasHeader(Protocol::HEADER_CONTENT_LENGTH)) {
                    // get content-length header
                    if (($contentLength = (int) $request->getHeader(Protocol::HEADER_CONTENT_LENGTH)) > 0) {
                        // check if given content length is not greater than post_max_size from php ini
                        if ($this->getPostMaxSize() < $contentLength) {
                            // throw 500 server error
                            throw new \Exception(sprintf("Post max size '%s' exceeded", $this->getPostMaxSize(false)), 500);
                        }
                        // copy connection stream to body stream by given content length
                        $request->copyBodyStream($connection->getConnectionResource(), $contentLength);
                        // get content out for oldschool query parsing todo: refactor query parsing
                        $content = $request->getBodyContent();
                        // check if request has to be parsed depending on Content-Type header
                        if ($queryParser->isParsingRelevant($request->getHeader(Protocol::HEADER_CONTENT_TYPE))) {
                            // initialize the array for the matches
                            $boundaryMatches = array();
                            // checks if request has multipart formdata or not
                            preg_match('/boundary=(.*)$/', $request->getHeader(Protocol::HEADER_CONTENT_TYPE), $boundaryMatches);
                            // check if boundaryMatches are found
                            // todo: refactor content string var to be able to use bodyStream
                            if (count($boundaryMatches) > 0) {
                                $parser->parseMultipartFormData($content);
                            } else {
                                $queryParser->parseStr($content);
                            }
                        }
                    }
                }

                // set parsed query and multipart form params to request
                $request->setParams($queryParser->getResult());

                // init connection & protocol server vars
                $this->initServerVars();

                // process modules by hook REQUEST_POST
                $this->processModules(ModuleHooks::REQUEST_POST);

                // if no module dispatched response throw internal server error 500
                if (! $response->hasState(HttpResponseStates::DISPATCH)) {
                    throw new \Exception('Response state is not dispatched', 500);
                }

            } catch (SocketReadTimeoutException $e) {
                // break the request processing due to client timeout
                break;
            } catch (SocketReadException $e) {
                // break the request processing due to peer reset
                break;
            } catch (SocketServerException $e) {
                // break the request processing
                break;
            } catch (\Exception $e) {
                // set status code given by exception
                // if 0 is comming set 500 by default
                $response->setStatusCode($e->getCode() ? $e->getCode() : 500);
                $this->renderErrorPage($e);
            }

            // process modules by hook RESPONSE_PRE
            $this->processModules(ModuleHooks::RESPONSE_PRE);

            // send response to connected client
            $this->prepareResponse();

            // send response to connected client
            $this->sendResponse();

            // process modules by hook RESPONSE_POST
            $this->processModules(ModuleHooks::RESPONSE_POST);

            // check if keep alive-loop is finished to close connection before log access and init vars
            // to avoid waiting on non keep alive requests for that
            if ($keepAliveConnection !== true) {
                $connection->close();
            }

            // log informations for access log etc...
            $this->logAccess();

            // init context vars afterwards to avoid performance issues
            $requestContext->initVars();

            // init the request parser for next request
            $parser->init();
        } while ($keepAliveConnection === true);

        // close connection if not closed yet
        $connection->close();
    }

    /**
     * Processes modules logic by given hook
     *
     * @param int $hook The hook identifier to process logic for
     *
     * @return void
     */
    protected function processModules($hook)
    {
        // get object refs to local vars
        $requestContext = $this->getRequestContext();
        $modules = $this->getModules();
        $request = $this->getParser()->getRequest();
        $response = $this->getParser()->getResponse();

        // interate all modules and call process by given hook
        foreach ($modules as $module) {
            /* @var $module \AppserverIo\WebServer\Interfaces\HttpModuleInterface */
            // process modules logic by hook
            $module->process($request, $response, $requestContext, $hook);
            // break chain if hook type is REQUEST_POST and response state is DISPATCH
            if ($hook === ModuleHooks::REQUEST_POST && $response->hasState(HttpResponseStates::DISPATCH)) {
                // break out
                break;
            }
        }
    }

    /**
     * Renders error page by given exception
     *
     * @param \Exception $exception The exception object
     *
     * @return void
     */
    public function renderErrorPage(\Exception $exception)
    {
        // get response ref to local var for template rendering
        $response = $this->getParser()->getResponse();
        // check if template is given and exists
        if (($errorsPageTemplatePath = $this->getRequestContext()->getServerVar(ServerVars::SERVER_ERRORS_PAGE_TEMPLATE_PATH)) && is_file($errorsPageTemplatePath)) {
            // render errors page
            ob_start();
            require $errorsPageTemplatePath;
            $errorsPage = ob_get_clean();
        } else {
            // build up error message manually without template
            $errorsPage = $response->getStatusCode() . ' ' . $response->getStatusReasonPhrase() . PHP_EOL . PHP_EOL . $exception->__toString() . PHP_EOL . PHP_EOL . strip_tags($this->getRequestContext()->getServerVar(ServerVars::SERVER_SIGNATURE));
        }
        // add content type to text/html
        $response->addHeader(HttpProtocol::HEADER_CONTENT_TYPE, HttpProtocol::HEADER_CONTENT_TYPE_VALUE_TEXT_HTML);
        // append errors page to response body
        $response->appendBodyStream($errorsPage);
    }

    /**
     * Prepares the response object to be ready for delivery
     *
     * @return void
     */
    public function prepareResponse()
    {
        // get local var refs
        $response = $this->getParser()->getResponse();

        // prepare headers in response object to be ready for delivery
        $response->prepareHeaders();
    }

    /**
     * Sends response to connected client
     *
     * @return void
     */
    public function sendResponse()
    {
        // get local var refs
        $response = $this->getParser()->getResponse();
        $inputStream = $response->getBodyStream();
        $connection = $this->getConnection();
        // try to rewind stream
        @rewind($inputStream);
        // write response status-line + headers
        $connection->write($response->getStatusLine() . $response->getHeaderString());
        // stream response to client connection
        while ($readContent = fread($inputStream, 4096)) {
            $connection->write($readContent);
        }
    }

    /**
     * Logs access information from request and response
     *
     * @return void
     */
    public function logAccess()
    {
        // get object refs to local var
        $request = $this->getParser()->getRequest();
        $response = $this->getParser()->getResponse();
        $requestContext = $this->getRequestContext();
        $serverContext = $this->getServerContext();
        $connection = $this->getConnection();
        $accessLogger = null;

        // lookup for dynamic logger configuration or take default access logger
        if ($requestContext->hasEnvVar(EnvVars::LOGGER_ACCESS)) {
            $accessLogger = $serverContext->getLogger($requestContext->getEnvVar(EnvVars::LOGGER_ACCESS));
        } else {
            $accessLogger = $serverContext->getLogger();
        }

        // log access information if AccessLogger exists
        if ($accessLogger) {
            // init datetime instance with current time and timezone
            $datetime = new \DateTime('now');
            // log access
            $accessLogger->info(
                sprintf(
                    /* This logs in apaches default combined format */
                    /* LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" combined */
                    '%s - - [%s] "%s %s %s" %s %s "%s" "%s"' . PHP_EOL,
                    $connection->getAddress(),
                    $datetime->format('d/M/Y:H:i:s O'),
                    $request->getMethod(),
                    $request->getUri(),
                    $request->getVersion(),
                    $response->getStatusCode(),
                    $response->hasHeader(Protocol::HEADER_CONTENT_LENGTH) ? $response->getHeader(Protocol::HEADER_CONTENT_LENGTH) : '-',
                    $request->hasHeader(Protocol::HEADER_REFERER) ? $request->getHeader(Protocol::HEADER_REFERER) : '-',
                    $request->hasHeader(Protocol::HEADER_USER_AGENT) ? $request->getHeader(Protocol::HEADER_USER_AGENT) : '-'
                )
            );
        }
    }

    /**
     * Inits the server vars by parsed request
     *
     * @return void
     */
    public function initServerVars()
    {

        // get request context to local var reference
        $requestContext = $this->getRequestContext();
        // get request to local var reference
        $request = $this->getParser()->getRequest();

        // set http protocol because this is the http connection class which implements http 1.1
        $requestContext->setServerVar(ServerVars::SERVER_PROTOCOL, Protocol::VERSION_1_1);

        // get http host to set server name var but trim the root domain
        $serverName = rtrim($request->getHeader(Protocol::HEADER_HOST), '.');
        if (strpos($serverName, ':') !== false) {
            $serverName = rtrim(strstr($serverName, ':', true), '.');
        }

        // set server name var
        $requestContext->setServerVar(ServerVars::SERVER_NAME, $serverName);

        // set http headers to server vars
        foreach ($request->getHeaders() as $headerName => $headerValue) {
            // set server vars by request
            $requestContext->setServerVar('HTTP_' . str_replace('-', '_', strtoupper($headerName)), $headerValue);
        }

        // set request method, query-string, uris and scheme
        $requestContext->setServerVar(ServerVars::REQUEST_METHOD, $request->getMethod());
        $requestContext->setServerVar(ServerVars::QUERY_STRING, $request->getQueryString());
        $requestContext->setServerVar(ServerVars::REQUEST_URI, $request->getUri());
        $requestContext->setServerVar(ServerVars::X_REQUEST_URI, $request->getUri());
        // this is the http connection handler, therefor we will rely on the https flag
        if ($requestContext->hasServerVar(ServerVars::HTTPS) && $requestContext->getServerVar(ServerVars::HTTPS) === ServerVars::VALUE_HTTPS_ON) {
            $requestContext->setServerVar(ServerVars::REQUEST_SCHEME, 'https');
        } else {
            $requestContext->setServerVar(ServerVars::REQUEST_SCHEME, 'http');
        }
    }

    /**
     * Registers the shutdown function in this context
     *
     * @return void
     */
    public function registerShutdown()
    {
        // register shutdown handler once to avoid strange memory consumption problems
        if ($this->hasRegisteredShutdown === false) {
            register_shutdown_function(array(
                &$this,
                "shutdown"
            ));
            $this->hasRegisteredShutdown = true;
        }
    }

    /**
     * Does shutdown logic for worker if something breaks in process
     *
     * @return void
     */
    public function shutdown()
    {
        // get refs to local vars
        $requestContext = $this->getRequestContext();
        $connection = $this->getConnection();
        $worker = $this->getWorker();
        $request = $this->getParser()->getRequest();
        $response = $this->getParser()->getResponse();
        $response->init();

        // check if connections is still alive
        if ($connection) {
            // call current fileahandler module's shutdown hook if exists
            if ($requestContext->hasServerVar(ServerVars::SERVER_HANDLER) &&
                $fileHandleModule = $this->getModule($requestContext->getServerVar(ServerVars::SERVER_HANDLER))
            ) {
                $fileHandleModule->process($request, $response, $requestContext, ModuleHooks::SHUTDOWN);
            }

            // check if filehandle module has not handled the shutdown and set the response state to dispatched
            // so do default shutdown / error handling for current worker process
            if (!$response->hasState(HttpResponseStates::DISPATCH)) {
                // set response code to 500 Internal Server Error
                $response->setStatusCode($response->getStatusCode());

                // add this header to prevent .php request to be cached
                $response->addHeader(Protocol::HEADER_EXPIRES, '19 Nov 1981 08:52:00 GMT');
                $response->addHeader(Protocol::HEADER_CACHE_CONTROL, 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                $response->addHeader(Protocol::HEADER_PRAGMA, 'no-cache');

                // get last error array
                $lastError = error_get_last();

                // check if it was a fatal error
                if (!is_null($lastError) && $lastError['type'] === 1) {
                    // set response code to 500 Internal Server Error
                    $response->setStatusCode(500);
                    $errorMessage = 'PHP Fatal error: ' . $lastError['message'] . ' in ' . $lastError['file'] . ' on line ' . $lastError['line'];
                    $this->renderErrorPage(new \RuntimeException($errorMessage, 500));
                }
            }

            // send response before shutdown
            $this->sendResponse();

            // close client connection
            $connection->close();
        }

        // check if worker is given
        if ($worker) {
            // call shutdown process on worker to respawn
            $this->getWorker()->shutdown();
        }
    }

    /**
     * Returns max post size in bytes if flag given
     *
     * @param boolean $asBytes If the return value should be bytes or string formated unit as given in ini
     *
     * @return int|string
     */
    public function getPostMaxSize($asBytes = true)
    {
        $postMaxSizeIniValue = ini_get('post_max_size');
        if ($asBytes === true) {
            $ini_v = trim($postMaxSizeIniValue);
            $s = array('g'=> 1<<30, 'm' => 1<<20, 'k' => 1<<10);
            return intval($ini_v) * ($s[strtolower(substr($ini_v, -1))] ?: 1);
        }
        return $postMaxSizeIniValue;
    }
}
