<?php
/**
 * \TechDivision\WebServer\ConnectionHandlers\
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Library
 * @package    TechDivision_WebServer
 * @subpackage ConnectionHandlers
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\ConnectionHandlers;

use TechDivision\Server\Dictionaries\ModuleHooks;
use TechDivision\Server\Dictionaries\ModuleVars;
use TechDivision\Server\Dictionaries\ServerVars;
use TechDivision\Server\Exceptions\ModuleException;
use TechDivision\Server\Interfaces\ConnectionHandlerInterface;
use TechDivision\Server\Interfaces\ModuleInterface;
use TechDivision\Server\Interfaces\ServerConfigurationInterface;
use TechDivision\Server\Interfaces\ServerContextInterface;
use TechDivision\Server\Interfaces\WorkerInterface;
use TechDivision\Server\Sockets\SocketInterface;
use TechDivision\Server\Sockets\SocketReadException;
use TechDivision\Server\Sockets\SocketReadTimeoutException;

use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpRequestParserInterface;
use TechDivision\Http\HttpProtocol;
use TechDivision\Http\HttpRequest;
use TechDivision\Http\HttpResponse;
use TechDivision\Http\HttpPart;
use TechDivision\Http\HttpQueryParser;
use TechDivision\Http\HttpRequestParser;
use TechDivision\Http\HttpResponseStates;
use TechDivision\Server\Sockets\SocketServerException;

/**
 * Class HttpConnectionHandler
 *
 * @category   Library
 * @package    TechDivision_WebServer
 * @subpackage ConnectionHandlers
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class HttpConnectionHandler implements ConnectionHandlerInterface
{

    /**
     * Hold's parser instance
     *
     * @var \TechDivision\Http\HttpRequestParserInterface
     */
    protected $parser;

    /**
     * Hold's the server context instance
     *
     * @var \TechDivision\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Hold's an array of modules to use for connection handler
     *
     * @var array
     */
    protected $modules;

    /**
     * Hold's errors page template
     *
     * @var string
     */
    protected $errorsPageTemplate;

    /**
     * Hold's the connection instance
     *
     * @var \TechDivision\Server\Sockets\SocketInterface
     */
    protected $connection;

    /**
     * Hold's the worker instance
     *
     * @var \TechDivision\Server\Interfaces\WorkerInterface
     */
    protected $worker;

    /**
     * Inits the connection handler by given context and params
     *
     * @param \TechDivision\Server\Interfaces\ServerContextInterface $serverContext The server's context
     * @param array                                                  $params        The params for connection handler
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
        $httpResponse->setDefaultHeaders(
            array(
                HttpProtocol::HEADER_SERVER =>  $serverContext->getServerVar(ServerVars::SERVER_SOFTWARE),
                HttpProtocol::HEADER_CONNECTION => HttpProtocol::HEADER_CONNECTION_VALUE_CLOSE
            )
        );

        // setup http parser
        $this->parser = new HttpRequestParser($httpRequest, $httpResponse);
        $this->parser->injectQueryParser(new HttpQueryParser());
        $this->parser->injectPart(new HttpPart());
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
     * Return's all needed modules as array for connection handler to process
     *
     * @return array An array of Modules
     */
    public function getModules()
    {
        return $this->modules;
    }


    /**
     * Return's a specific module instance by given name
     *
     * @param string $name The modules name to return an instance for
     *
     * @return \TechDivision\Server\Interfaces\ModuleInterface|null
     */
    public function getModule($name)
    {
        if (isset($this->modules[$name])) {
            return $this->modules[$name];
        }
    }

    /**
     * Return's the server context instance
     *
     * @return \TechDivision\Server\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }

    /**
     * Return's the server's configuration
     *
     * @return \TechDivision\Server\Interfaces\ServerConfigurationInterface
     */
    public function getServerConfig()
    {
        return $this->getServerContext()->getServerConfig();
    }

    /**
     * Return's the parser instance
     *
     * @return \TechDivision\Http\HttpRequestParserInterface
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Return's the connection used to handle with
     *
     * @return \TechDivision\Server\Sockets\SocketInterface
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * Return's the worker instance which starte this worker thread
     *
     * @return \TechDivision\Server\Interfaces\WorkerInterface
     */
    protected function getWorker()
    {
        return $this->worker;
    }

    /**
     * Return's the template for errors page to render
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
     * @param \TechDivision\Server\Sockets\SocketInterface    $connection The connection to handle
     * @param \TechDivision\Server\Interfaces\WorkerInterface $worker     The worker how started this handle
     *
     * @return bool Weather it was responsible to handle the firstLine or not.
     */
    public function handle(SocketInterface $connection, WorkerInterface $worker)
    {
        // register shutdown handler
        register_shutdown_function(array(&$this, "shutdown"));

        // add connection ref to self
        $this->connection = $connection;
        $this->worker = $worker;

        // get instances for short calls
        $serverContext = $this->getServerContext();
        $serverConfig = $serverContext->getServerConfig();
        $parser = $this->getParser();

        // Get our query parser
        $queryParser = $parser->getQueryParser();

        // Get the request and response
        $request = $parser->getRequest();
        $response = $parser->getResponse();

        // init keep alive settings
        $keepAliveTimeout = (int)$serverConfig->getKeepAliveTimeout();
        $keepAliveMax = (int)$serverConfig->getKeepAliveMax();

        do {
            // try to handle request if its a http request
            try {

                // reset connection infos to server vars
                $serverContext->setConnectionServerVars($connection);

                // time settings
                $serverContext->setServerVar(ServerVars::REQUEST_TIME, time());

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

                // init the request parser
                $parser->init();

                // process modules by hook REQUEST_PRE
                $this->processModules(ModuleHooks::REQUEST_PRE);

                // init keep alive connection flag
                $keepAliveConnection = false;

                // set first line from connection
                $line = $connection->readLine(2048, $keepAliveTimeout);

                /**
                 * In the interest of robustness, servers SHOULD ignore any empty
                 * line(s) received where a Request-Line is expected. In other words, if
                 * the server is reading the protocol stream at the beginning of a
                 * message and receives a CRLF first, it should ignore the CRLF.
                 *
                 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.1
                 */
                if ($line === "\r\n") {
                    // ignore the first CRLF and go on reading the expected start-line.
                    $line = $connection->readLine(2048, $keepAliveTimeout);
                }

                // parse read line
                $parser->parseStartLine($line);

                /**
                 * Parse headers in a proper way
                 *
                 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
                 */
                $messageHeaders = '';
                while ($line != "\r\n") {
                    // read next line
                    $line = $connection->readLine();
                    // enhance headers
                    $messageHeaders .= $line;
                }

                // parse headers
                $parser->parseHeaders($messageHeaders);

                // process connection type keep-alive
                if (strcasecmp(
                    $request->getHeader(HttpProtocol::HEADER_CONNECTION),
                    HttpProtocol::HEADER_CONNECTION_VALUE_KEEPALIVE
                ) === 0) {
                    // only if max connections were not reached yet
                    if ($keepAliveMax > 0) {
                        // enable keep alive connection
                        $keepAliveConnection = true;
                        // set keep-alive headers
                        $response->addHeader(HttpProtocol::HEADER_CONNECTION, HttpProtocol::HEADER_CONNECTION_VALUE_KEEPALIVE);
                        $response->addHeader(HttpProtocol::HEADER_KEEP_ALIVE, "timeout: $keepAliveTimeout, max: $keepAliveMax");
                        // decrease keep-alive max
                        --$keepAliveMax;
                    }
                }

                // check if message body will be transmitted
                if ($request->hasHeader(HttpProtocol::HEADER_CONTENT_LENGTH)) {
                    // get content-length header
                    if (($contentLength = (int)$request->getHeader(HttpProtocol::HEADER_CONTENT_LENGTH)) > 0) {
                        // copy connection stream to body stream by given content length
                        $request->copyBodyStream($connection->getConnectionResource(), $contentLength);
                        // get content out for oldschool query parsing todo: refactor query parsing
                        $content = $request->getBodyContent();
                        // check if request has to be parsed depending on Content-Type header
                        if ($queryParser->isParsingRelevant($request->getHeader(HttpProtocol::HEADER_CONTENT_TYPE))) {
                            // checks if request has multipart formdata or not
                            preg_match('/boundary=(.*)$/', $request->getHeader(HttpProtocol::HEADER_CONTENT_TYPE), $boundaryMatches);
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
                if (!$response->hasState(HttpResponseStates::DISPATCH)) {
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
                $response->setStatusCode($e->getCode());
                $this->renderErrorPage($e->__toString());
            }

            // process modules by hook RESPONSE_PRE
            $this->processModules(ModuleHooks::RESPONSE_PRE);

            // send response to connected client
            $this->prepareResponse();

            // process modules by hook RESPONSE_PRE
            $this->processModules(ModuleHooks::RESPONSE_POST);

            // send response to connected client
            $this->sendResponse();

            // log informations for access log etc...
            $this->logAccess();

            // init context vars afterwards to avoid performance issues
            $serverContext->initVars();

        } while ($keepAliveConnection === true);

        // finally close connection
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
        $modules = $this->getModules();
        $request = $this->getParser()->getRequest();
        $response = $this->getParser()->getResponse();

        // interate all modules and call process by given hook
        foreach ($modules as $module) {
            /* @var $module \TechDivision\Server\Interfaces\ModuleInterface */
            // process modules logic by hook
            $module->process($request, $response, $hook);
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
     * @param string $errorMessage The error message string to render
     *
     * @return void
     */
    public function renderErrorPage($errorMessage)
    {
        // get response ref to local var for template rendering
        $response = $this->getParser()->getResponse();
        // check if template is given and exists
        if (($errorsPageTemplatePath = $this->getServerContext()->getServerVar(ServerVars::SERVER_ERRORS_PAGE_TEMPLATE_PATH))
            && is_file($errorsPageTemplatePath)) {
            // render errors page
            ob_start();
            require $errorsPageTemplatePath;
            $errorsPage = ob_get_clean();
        } else {
            // build up error message manually without template
            $errorsPage = $response->getStatusCode() . ' ' . $response->getStatusReasonPhrase() .
                PHP_EOL . PHP_EOL . $errorMessage .
                PHP_EOL . PHP_EOL . strip_tags($this->getServerContext()->getServerVar(ServerVars::SERVER_SIGNATURE));
        }
        // append errors page to response body
        $response->appendBodyStream($errorsPage);
    }

    /**
     * Prepare's the response object to be ready for delivery
     *
     * @return void
     */
    public function prepareResponse()
    {
        // get local var refs
        $response = $this->getParser()->getResponse();
        // prepare headers in response object to be ready for delivery
        $response->prepareHeaders();
        // set current date before sending it
        $response->addHeader(HttpProtocol::HEADER_DATE, gmdate(DATE_RFC822));
    }

    /**
     * Send's response to connected client
     *
     * @return void
     */
    public function sendResponse()
    {
        // get local var refs
        $response = $this->getParser()->getResponse();
        $connection = $this->getConnection();
        // write response status-line
        $connection->write($response->getStatusLine());
        // write response headers
        $connection->write($response->getHeaderString());
        // stream response body to connection
        $connection->copyStream($response->getBodyStream());
    }

    /**
     * Log's access information from request and response
     *
     * @return void
     */
    public function logAccess()
    {
        // get object refs to local var
        $request = $this->getParser()->getRequest();
        $response = $this->getParser()->getResponse();
        $serverContext = $this->getServerContext();

        // log access information if AccessLogger exists
        if ($accessLogger = $this->getServerContext()->getLogger(ServerVars::LOGGER_ACCESS)) {

            // init datetime instance with current time and timezone
            $datetime = new \DateTime('now');

            $accessLogger->info(
                sprintf(
                    /* This logs in apaches default combined format */
                    /* LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" combined */
                    '%s - - [%s] "%s %s %s" %s %s "%s" "%s"' . PHP_EOL,
                    $serverContext->getServerVar(ServerVars::REMOTE_ADDR),
                    $datetime->format('d/M/Y:H:i:s O'),
                    $request->getMethod(),
                    $request->getUri(),
                    $request->getVersion(),
                    $response->getStatusCode(),
                    $response->hasHeader(HttpProtocol::HEADER_CONTENT_LENGTH) ? $response->getHeader(HttpProtocol::HEADER_CONTENT_LENGTH) : '-',
                    $request->hasHeader(HttpProtocol::HEADER_REFERER) ? $request->getHeader(HttpProtocol::HEADER_REFERER) : '-',
                    $request->hasHeader(HttpProtocol::HEADER_USER_AGENT) ? $request->getHeader(HttpProtocol::HEADER_USER_AGENT) : '-'
                )
            );
        }
    }

    /**
     * Init's the server vars by parsed request
     *
     * @return void
     */
    public function initServerVars()
    {
        // get server context to local var reference
        $serverContext = $this->getServerContext();
        // get request to local var reference
        $request = $this->getParser()->getRequest();

        // set http protocol because this is the http connection class which implements http 1.1
        $serverContext->setServerVar(ServerVars::SERVER_PROTOCOL, 'HTTP/1.1');

        // get http host to set server name var but trim the root domain
        $serverName = rtrim($request->getHeader(HttpProtocol::HEADER_HOST), '.');
        if (strpos($serverName, ':') !== false) {
            $serverName = rtrim(strstr($serverName, ':', true), '.');
        }

        // set server name var
        $serverContext->setServerVar(ServerVars::SERVER_NAME, $serverName);

        // set server vars by request
        $serverContext->setServerVar(
            ServerVars::HTTP_USER_AGENT,
            $request->getHeader(HttpProtocol::HEADER_USER_AGENT)
        );
        $serverContext->setServerVar(
            ServerVars::HTTP_REFERER,
            $request->getHeader(HttpProtocol::HEADER_REFERER)
        );
        $serverContext->setServerVar(
            ServerVars::HTTP_COOKIE,
            $request->getHeader(HttpProtocol::HEADER_COOKIE)
        );
        $serverContext->setServerVar(
            ServerVars::HTTP_HOST,
            $request->getHeader(HttpProtocol::HEADER_HOST)
        );
        $serverContext->setServerVar(
            ServerVars::HTTP_X_REQUESTED_WITH,
            $request->getHeader(HttpProtocol::HEADER_X_REQUESTED_WITH)
        );
        $serverContext->setServerVar(
            ServerVars::HTTP_ACCEPT,
            $request->getHeader(HttpProtocol::HEADER_ACCEPT)
        );
        $serverContext->setServerVar(
            ServerVars::HTTP_ACCEPT_CHARSET,
            $request->getHeader(HttpProtocol::HEADER_ACCEPT_CHARSET)
        );
        $serverContext->setServerVar(
            ServerVars::HTTP_ACCEPT_ENCODING,
            $request->getHeader(HttpProtocol::HEADER_ACCEPT_ENCODING)
        );
        $serverContext->setServerVar(
            ServerVars::HTTP_ACCEPT_LANGUAGE,
            $request->getHeader(HttpProtocol::HEADER_ACCEPT_LANGUAGE)
        );
        $serverContext->setServerVar(
            ServerVars::HTTP_CONNECTION,
            $request->getHeader(HttpProtocol::HEADER_CONNECTION)
        );
        $serverContext->setServerVar(
            ServerVars::HTTP_FORWARDED,
            $request->getHeader(HttpProtocol::HEADER_X_FORWARD)
        );
        $serverContext->setServerVar(
            ServerVars::HTTP_PROXY_CONNECTION,
            $request->getHeader(HttpProtocol::HEADER_PROXY_CONNECTION)
        );
        $serverContext->setServerVar(
            ServerVars::REQUEST_METHOD,
            $request->getMethod()
        );
        $serverContext->setServerVar(
            ServerVars::QUERY_STRING,
            $request->getQueryString()
        );
        $serverContext->setServerVar(
            ServerVars::REQUEST_URI,
            $request->getUri()
        );
        $serverContext->setServerVar(
            ServerVars::X_REQUEST_URI,
            $request->getUri()
        );
    }

    /**
     * Does shutdown logic for worker if something breaks in process
     *
     * @return void
     */
    public function shutdown()
    {
        error_log(__METHOD__);
        // get refs to local vars
        $serverContext = $this->getServerContext();
        $connection = $this->getConnection();
        $worker = $this->getWorker();
        $request = $this->getParser()->getRequest();
        $response = $this->getParser()->getResponse();

        // check if connections is still alive
        if ($connection) {

            // call current fileahandler module's shutdown hook if exists
            if ($fileHandleModule = $this->getModule($serverContext->getServerVar(ServerVars::SERVER_HANDLER))) {
                $fileHandleModule->process($request, $response, ModuleHooks::SHUTDOWN);
            }

            // check if filehandle module has not handled the shutdown and set the response state to dispatched
            // so do default shutdown / error handling for current worker process
            if (!$response->hasState(HttpResponseStates::DISPATCH)) {
                // set response code to 500 Internal Server Error
                $response->setStatusCode(appserver_get_http_response_code());

                // add this header to prevent .php request to be cached
                $response->addHeader(HttpProtocol::HEADER_EXPIRES, '19 Nov 1981 08:52:00 GMT');
                $response->addHeader(HttpProtocol::HEADER_CACHE_CONTROL, 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                $response->addHeader(HttpProtocol::HEADER_PRAGMA, 'no-cache');

                // get last error array
                $lastError = error_get_last();

                // check if it was a fatal error
                if (!is_null($lastError) && $lastError['type'] === 1) {

                    // set response code to 500 Internal Server Error
                    $response->setStatusCode(500);
                    $errorMessage = 'PHP Fatal error: ' . $lastError['message'] .
                        ' in ' . $lastError['file'] . ' on line ' . $lastError['line'];
                    $this->renderErrorPage($errorMessage);
                }

                // grep headers and set to response object
                foreach (appserver_get_headers(true) as $i => $h) {
                    // set headers defined in sapi headers
                    $h = explode(':', $h, 2);
                    if (isset($h[1])) {
                        // load header key and value
                        $key = trim($h[0]);
                        $value = trim($h[1]);
                        // if no status, add the header normally
                        if ($key === HttpProtocol::HEADER_STATUS) {
                            // set status by Status header value which is only used by fcgi sapi's normally
                            $response->setStatus($value);
                        } else {
                            $response->addHeader($key, $value);
                        }
                    }
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
}
