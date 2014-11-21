<?php

/**
 * \AppserverIo\WebServer\Modules\PhpModule
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
use AppserverIo\WebServer\Modules\Php\Globals;
use AppserverIo\WebServer\Modules\Php\ProcessThread\ProcessThread;

/**
 * Class PhpModule
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
class PhpModule implements HttpModuleInterface
{
    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'php';

    /**
     * Defines the php specific server var PHP_SELF
     *
     * @var string
     */
    const SERVER_VAR_PHP_SELF = 'PHP_SELF';

    /**
     * Hold's the server's context
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Hold's the request's context
     *
     * @var \AppserverIo\Server\Interfaces\RequestContextInterface
     */
    protected $requestContext;

    /**
     * Hold's the request instance
     *
     * @var \AppserverIo\Psr\HttpMessage\RequestInterface
     */
    protected $request;

    /**
     * Hold's the response instance
     *
     * @var \AppserverIo\Psr\HttpMessage\ResponseInterface
     */
    protected $response;

    /**
     * Hold's the globals for php process to call
     *
     * @var array
     */
    protected $globals = array();

    /**
     * Hold's the uploaded filename's
     *
     * @var array
     */
    protected $uploadedFiles;

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
        $this->uploadedFiles = array();
    }

    /**
     * Return's the server's context
     *
     * @return \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }

    /**
     * Return's the request instance
     *
     * @return \AppserverIo\Psr\HttpMessage\RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Return's the response instance
     *
     * @return \AppserverIo\Psr\HttpMessage\ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
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

    /**
     * Return's the request's context instance
     *
     * @return \AppserverIo\Server\Interfaces\RequestContextInterface
     */
    public function getRequestContext()
    {
        return $this->requestContext;
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
    public function process(
        RequestInterface $request,
        ResponseInterface $response,
        RequestContextInterface $requestContext,
        $hook
    ) {
        // In php an interface is, by definition, a fixed contract. It is immutable.
        // So we have to declair the right ones afterwards...
        /** @var $request \AppserverIo\Psr\HttpMessage\RequestInterface */
        /** @var $request \AppserverIo\Psr\HttpMessage\ResponseInterface */

        // check if shutdown hook is comming
        if (ModuleHooks::SHUTDOWN === $hook) {
            return $this->shutdown($request, $response);
        }

        // if wrong hook is comming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }

        // set request context as member ref
        $this->requestContext = $requestContext;

        // set req and res internally
        $this->request = $request;
        $this->response = $response;

        // check if server handler sais php modules should react on this request as file handler
        if ($requestContext->getServerVar(ServerVars::SERVER_HANDLER) === self::MODULE_NAME) {

            // check if file does not exist
            if (!$requestContext->hasServerVar(ServerVars::SCRIPT_FILENAME)) {
                // send 404
                $response->setStatusCode(404);
                throw new ModuleException(null, 404);
            }

            // init script filename var
            $scriptFilename = $requestContext->getServerVar(ServerVars::SCRIPT_FILENAME);

            /**
             * Check if script name exists on filesystem
             * This is necessary because of seq faults if a non existing file will be required.
             */
            if (!file_exists($scriptFilename)) {
                // send 404
                $response->setStatusCode(404);
                throw new ModuleException(null, 404);
            }

            /**
             * todo: fill up those server vars in future when mod auth is present
             *
             * PHP_AUTH_DIGEST
             * PHP_AUTH_USER
             * PHP_AUTH_PW
             */

            // prepare modules specific server vars
            $this->prepareServerVars();

            // initialize the globals $_SERVER, $_REQUEST, $_POST, $_GET, $_COOKIE, $_FILES and set the headers
            $this->initGlobals();

            // start new php process
            $process = new ProcessThread(
                $scriptFilename,
                $this->globals,
                $this->uploadedFiles
            );

            // start process
            $process->start(PTHREADS_INHERIT_ALL | PTHREADS_ALLOW_HEADERS);
            // wait for process to finish
            $process->join();

            // check if process fatal error occurred so throw module exception because the modules process class
            // is not responsible for set correct headers and messages for error's in module context
            if ($lastError = $process->getLastError()) {
                // check if last error was a fatal one
                if ($lastError['type'] === E_ERROR || $lastError['type'] === E_USER_ERROR) {
                    // check if output buffer was set by the application executed by the php process
                    // so do not override content by exception stack trace
                    if (strlen($errorMessage = $process->getOutputBuffer()) === 0) {
                        $errorMessage = 'PHP Fatal error: ' . $lastError['message'] .
                            ' in ' . $lastError['file'] . ' on line ' . $lastError['line'];
                    }
                    // set internal server error code with error message to exception
                    throw new ModuleException($errorMessage, 500);
                }
            }

            // prepare response
            $this->prepareResponse($process);

            // store the file's contents in the response
            $response->appendBodyStream(
                $process->getOutputBuffer()
            );

            // set response state to be dispatched after this without calling other modules process
            $response->setState(HttpResponseStates::DISPATCH);
        }
    }

    /**
     * Prepares the response instance for delivery
     *
     * @param \AppserverIo\WebServer\Modules\Php\ProcessThread $process The process to prepare response for
     *
     * @return void
     */
    public function prepareResponse($process)
    {
        // get response instance to local var reference
        $response = $this->getResponse();

        // add x powered
        $response->addHeader(Protocol::HEADER_X_POWERED_BY, __CLASS__);

        // read out status code and set if exists
        if ($responseCode = $process->getHttpResponseCode()) {
            $response->setStatusCode($responseCode);
        }

        // add this header to prevent .php request to be cached
        $response->addHeader(Protocol::HEADER_EXPIRES, '19 Nov 1981 08:52:00 GMT');
        $response->addHeader(Protocol::HEADER_CACHE_CONTROL, 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $response->addHeader(Protocol::HEADER_PRAGMA, 'no-cache');

        // set per default text/html mimetype
        $response->addHeader(Protocol::HEADER_CONTENT_TYPE, 'text/html');
        // check if headers are given
        if (is_array($process->getHttpHeaders())) {
            // grep headers and set to response object
            foreach ($process->getHttpHeaders() as $i => $h) {
                // set headers defined in sapi headers
                $h = explode(':', $h, 2);
                if (isset($h[1])) {
                    // load header key and value
                    $key = trim($h[0]);
                    $value = trim($h[1]);
                    // if no status, add the header normally
                    if ($key === Protocol::HEADER_STATUS) {
                        // set status by Status header value which is only used by fcgi sapi's normally
                        $response->setStatus($value);
                    } else {
                        $response->addHeader($key, $value);
                    }
                }
            }
        }
    }

    /**
     * Prepare's the server vars for php usage
     *
     * @return void
     */
    protected function prepareServerVars()
    {
        $requestContext = $this->getRequestContext();
        // init php self server var
        $phpSelf = $requestContext->getServerVar(ServerVars::SCRIPT_NAME);
        if ($requestContext->hasServerVar(ServerVars::PATH_INFO)) {
            $phpSelf .= $requestContext->getServerVar(ServerVars::PATH_INFO);
        }
        $requestContext->setServerVar(self::SERVER_VAR_PHP_SELF, $phpSelf);
    }

    /**
     * Initialize the PHP globals necessary for legacy mode and backward compatibility
     * for standard applications.
     *
     * @return void
     */
    protected function initGlobals()
    {
        $request = $this->getRequest();
        $requestContext = $this->getRequestContext();

        // Init the actual globals storage and make sure to generate it anew
        $this->globals = new Globals();
        $globals = $this->globals;

        // initialize the globals
        $globals['server'] = $requestContext->getServerVars();
        $globals['env'] = array_merge(
            (array)$requestContext->getEnvVars(),
            appserver_get_envs()
        );
        $globals['request'] = $request->getParams();

        // init post / get. default init vars as GET method case
        if ($requestContext->getServerVar(ServerVars::REQUEST_METHOD) === Protocol::METHOD_GET) {
            // clear post array
            $globals['post'] = array();
            // set all params to get
            $globals['get'] = $request->getParams();
        }
        // check if method post was given
        if ($request->getMethod() === Protocol::METHOD_POST) {
            // set raw request if post method is going on
            $globals['httpRawPostData'] = $request->getBodyContent();
            // set params to post
            $globals['post'] = $request->getParams();
            $globals['get'] = array();
            // set params given in query string to get if query string exists
            if ($requestContext->hasServerVar(ServerVars::QUERY_STRING)) {
                parse_str($requestContext->getServerVar(ServerVars::QUERY_STRING), $getArray);
                $globals['get'] = $getArray;
            }
        }
        // set cookie globals
        $cookies = array();
        // iterate all cookies and set them in globals if exists
        if ($cookieHeaderValue = $request->getHeader(Protocol::HEADER_COOKIE)) {
            foreach (explode(';', $cookieHeaderValue) as $cookieLine) {
                list ($key, $value) = explode('=', $cookieLine);
                $cookies[trim($key)] = trim($value);
            }
        }
        $globals['cookie'] = $cookies;
        // set files globals
        $globals['files'] = $this->initFileGlobals($request);
    }

    /**
     * Returns the array with the $_FILES vars.
     *
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface $request The request instance
     *
     * @return array The $_FILES vars
     */
    protected function initFileGlobals(\AppserverIo\Psr\HttpMessage\RequestInterface $request)
    {
        // init query str
        $queryStr = '';

        // iterate all files
        foreach ($request->getParts() as $part) {
            // check if filename is given, write and register it
            if ($part->getFilename()) {
                // generate temp filename
                $tempName = tempnam(ini_get('upload_tmp_dir'), 'php');
                // write part
                $part->write($tempName);
                // register uploaded file
                $this->registerFileUpload($tempName);
                // init error state
                $errorState = UPLOAD_ERR_OK;
            } else {
                // set error state
                $errorState = UPLOAD_ERR_NO_FILE;
                // clear tmp file
                $tempName = '';
            }
            // check if file has array info
            if (preg_match('/^([^\[]+)(\[.+)?/', $part->getName(), $matches)) {

                // get first part group name and array definition if exists
                $partGroup = $matches[1];
                $partArrayDefinition = '';
                if (isset($matches[2])) {
                    $partArrayDefinition = $matches[2];
                }
                $queryStr .= $partGroup . '[name]' . $partArrayDefinition . '=' . $part->getFilename() .
                    '&' . $partGroup . '[type]' . $partArrayDefinition . '=' . $part->getContentType() .
                    '&' . $partGroup . '[tmp_name]' . $partArrayDefinition . '=' . $tempName .
                    '&' . $partGroup . '[error]' . $partArrayDefinition . '=' . $errorState .
                    '&' . $partGroup . '[size]' . $partArrayDefinition . '=' . $part->getSize() . '&';
            }
        }
        // parse query string to array
        parse_str($queryStr, $filesArray);

        // return files array finally.
        return $filesArray;
    }

    /**
     * Register's a file upload on internal php hash table for being able to use core functions
     * like move_uploaded_file or is_uploaded_file as usual.
     *
     * @param string $filename The filename to register
     *
     * @return bool
     */
    public function registerFileUpload($filename)
    {
        // add filename to uploaded file array
        $this->uploadedFiles[] = $filename;
        // registers file upload in this context for php process without threading
        return appserver_register_file_upload($filename);
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
     * Implement's module shutdown logic
     *
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface  $request  The request object
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface $response The response object
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function shutdown(HttpRequestInterface $request, HttpResponseInterface $response)
    {
        // todo: if non thread process is used than here should be the shutdown handling
        // if exit/die or fatal error happens in this context so that the worker will be
        // restarted and the module hook for restart will be called to trigger this function
    }
}
