<?php
/**
 * \TechDivision\WebServer\Modules\PhpModule
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
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Modules;

use TechDivision\Http\HttpProtocol;
use TechDivision\Http\HttpResponseStates;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\WebServer\Dictionaries\ServerVars;
use TechDivision\WebServer\Interfaces\ModuleInterface;
use TechDivision\WebServer\Exceptions\ModuleException;
use TechDivision\WebServer\Interfaces\ServerContextInterface;

/**
 * Class PhpModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class PhpModule implements ModuleInterface
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
     * @var \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Hold's the request instance
     *
     * @var \TechDivision\Http\HttpRequestInterface
     */
    protected $request;

    /**
     * Hold's the response instance
     *
     * @var \TechDivision\Http\HttpResponseInterface
     */
    protected $response;

    /**
     * Hold's the globals for php process to call
     *
     * @var \TechDivision\WebServer\Modules\PhpGlobals
     */
    protected $globals;

    /**
     * Hold's the uploaded filename's
     *
     * @var array
     */
    protected $uploadedFiles;

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
        $this->globals = new PhpGlobals();
        $this->uploadedFiles = array();
    }

    /**
     * Return's the server's context
     *
     * @return \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }

    /**
     * Return's the request instance
     *
     * @return \TechDivision\Http\HttpRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Return's the response instance
     *
     * @return \TechDivision\Http\HttpResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Implement's module logic
     *
     * @param \TechDivision\Http\HttpRequestInterface  $request  The request object
     * @param \TechDivision\Http\HttpResponseInterface $response The response object
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function process(HttpRequestInterface $request, HttpResponseInterface $response)
    {
        // set req and res internally
        $this->request = $request;
        $this->response = $response;
        // get server context to local var
        $serverContext = $this->getServerContext();

        // check if server handler sais php modules should react on this request as file handler
        if ($serverContext->getServerVar(ServerVars::SERVER_HANDLER) === self::MODULE_NAME) {

            // check if file does not exist
            if (!$serverContext->hasServerVar(ServerVars::SCRIPT_FILENAME)) {
                // send 404
                $response->setStatusCode(404);
                throw new ModuleException(null, 404);
            }

            // init script filename var
            $scriptFilename = $serverContext->getServerVar(ServerVars::SCRIPT_FILENAME);

            /**
             * Check if script name exists on filesystem
             * This is necessary because of seq faults if a non existing file will be required.
             */
            if (!file_exists($scriptFilename)) {
                return;
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
            $process = new PhpProcessThread(
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
                if ($lastError['type'] === 1) {
                    $errorMessage = 'PHP Fatal error: ' . $lastError['message'] .
                        ' in ' . $lastError['file'] . ' on line ' . $lastError['line'];
                    // set internal server error code with error mesage to exception
                    throw new ModuleException($errorMessage, 500);
                }
            }

            // prepare response
            $this->prepareResponse(
                $process->getHeaders()
            );

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
     * @param array $headers The headers to prepare
     *
     * @return void
     */
    public function prepareResponse($headers)
    {
        // get response instance to local var reference
        $response = $this->getResponse();
        // add this header to prevent .php request to be cached
        $response->addHeader(HttpProtocol::HEADER_EXPIRES, '19 Nov 1981 08:52:00 GMT');
        // set per default text/html mimetype
        $response->addHeader(HttpProtocol::HEADER_CONTENT_TYPE, 'text/html');
        // grep headers and set to response object
        foreach ($headers as $i => $h) {
            // set headers defined in sapi headers
            $h = explode(':', $h, 2);
            if (isset($h[1])) {
                // load header key and value
                $key = trim($h[0]);
                $value = trim($h[1]);
                // if no status, add the header normally
                if ($key === HttpProtocol::HEADER_STATUS) {
                    $response->setStatus($value);
                } else {
                    $response->addHeader($key, $value);
                }
                // set status header to 301 if location is given
                if ($key == HttpProtocol::HEADER_LOCATION) {
                    $response->setStatusCode(301);
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
        $serverContext = $this->getServerContext();
        // init php self server var
        $phpSelf = $serverContext->getServerVar(ServerVars::SCRIPT_NAME);
        if ($serverContext->hasServerVar(ServerVars::PATH_INFO)) {
            $phpSelf .= $serverContext->getServerVar(ServerVars::PATH_INFO);
        }
        $serverContext->setServerVar(self::SERVER_VAR_PHP_SELF, $phpSelf);
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
        $globals = $this->globals;

        // initialize the globals
        $globals->server = $this->getServerContext()->getServerVars();
        $globals->request = $request->getParams();

        // init post / get. default init vars as GET method case
        if ($this->getServerContext()->getServerVar(ServerVars::REQUEST_METHOD) === HttpProtocol::METHOD_GET) {
            // clear post array
            $globals->post = array();
            // set all params to get
            $globals->get = $request->getParams();
        }
        // check if method post was given
        if ($request->getMethod() === HttpProtocol::METHOD_POST) {
            // set params to post
            $globals->post = $request->getParams();
            $globals->get = array();
            // set params given in query string to get if query string exists
            if ($this->getServerContext()->hasServerVar(ServerVars::QUERY_STRING)) {
                parse_str($this->getServerContext()->getServerVar(ServerVars::QUERY_STRING), $getArray);
                $globals->get = $getArray;
            }
        }
        // set cookie globals
        $globals->cookie = array();
        // iterate all cookies and set them in globals if exists
        if ($cookieHeaderValue = $request->getHeader(HttpProtocol::HEADER_COOKIE)) {
            foreach (explode('; ', $cookieHeaderValue) as $cookieLine) {
                list ($key, $value) = explode('=', $cookieLine);
                $globals->cookie[$key] = $value;
            }
        }
        // set files globals
        $globals->files = $this->initFileGlobals($request);
    }

    /**
     * Returns the array with the $_FILES vars.
     *
     * @param \TechDivision\Http\HttpRequestInterface $request The request instance
     *
     * @return array The $_FILES vars
     */
    protected function initFileGlobals(\TechDivision\Http\HttpRequestInterface $request)
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
     * @param string $filename the filename to register
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
}
