<?php
/**
 * \TechDivision\WebServer\Modules\DirectoryModule
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

use TechDivision\Connection\ConnectionRequestInterface;
use TechDivision\Connection\ConnectionResponseInterface;
use TechDivision\Http\HttpProtocol;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\Http\HttpResponseStates;
use TechDivision\Server\Dictionaries\ModuleHooks;
use TechDivision\Server\Interfaces\ModuleInterface;
use TechDivision\Server\Exceptions\ModuleException;
use TechDivision\Server\Interfaces\RequestContextInterface;
use TechDivision\Server\Interfaces\ServerContextInterface;
use TechDivision\Server\Dictionaries\ServerVars;

/**
 * Class DirectoryModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class DirectoryModule implements ModuleInterface
{
    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'directory';

    /**
     * Hold's the server context instance
     *
     * @var \TechDivision\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Return's the request instance
     *
     * @return \TechDivision\Http\HttpRequestInterface The request instance
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the response instance
     *
     * @return \TechDivision\Http\HttpResponseInterface The response instance;
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Initiates the module
     *
     * @param \TechDivision\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
        $this->serverContext= $serverContext;
        // save directory index as array got space separated from config
        $this->directoryIndex = explode(' ', $serverContext->getServerConfig()->getDirectoryIndex());
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
     * Implement's module logic for given hook
     *
     * @param \TechDivision\Connection\ConnectionRequestInterface     $request        A request object
     * @param \TechDivision\Connection\ConnectionResponseInterface    $response       A response object
     * @param \TechDivision\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     * @param int                                                     $hook           The current hook to process logic for
     *
     * @return bool
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function process(
        ConnectionRequestInterface $request,
        ConnectionResponseInterface $response,
        RequestContextInterface $requestContext,
        $hook
    ) {
        // In php an interface is, by definition, a fixed contract. It is immutable.
        // So we have to declair the right ones afterwards...
        /** @var $request \TechDivision\Http\HttpRequestInterface */
        /** @var $response \TechDivision\Http\HttpResponseInterface */

        // if false hook is comming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }

        // set req and res object internally
        $this->request = $request;
        $this->response = $response;
        // get server context ref to local func
        $serverContext = $this->getServerContext();

        // get document root
        $documentRoot = $requestContext->getServerVar(ServerVars::DOCUMENT_ROOT);
        // get url
        $url = parse_url($requestContext->getServerVar(ServerVars::X_REQUEST_URI), PHP_URL_PATH);
        // get query string with asterisk
        $queryString = strstr($requestContext->getServerVar(ServerVars::X_REQUEST_URI), '?');

        // get read path to requested uri
        $realPath = $documentRoot . $url;

        // check if it's a dir
        if (is_dir($realPath)|| $url === '/') {
            // check if uri has trailing slash
            if (substr($url, -1) !== '/') {
                // set enhance uri with trailing slash to response
                $response->addHeader(HttpProtocol::HEADER_LOCATION, $url . '/' . $queryString);
                // send redirect status
                $response->setStatusCode(301);
                // set response state to be dispatched after this without calling other modules process
                $response->setState(HttpResponseStates::DISPATCH);
            } else {

                // check directory index definitions
                foreach ($this->getDirectoryIndex() as $index) {
                    // check if defined index files are found in directory
                    if (is_file($realPath . $index)) {
                        // reset uri with indexed filename
                        $requestContext->setServerVar(ServerVars::X_REQUEST_URI, $url . $index . $queryString);
                        // break out if index file was found
                        return true;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Return's the directory index as array
     *
     * @return array
     */
    public function getDirectoryIndex()
    {
        return $this->directoryIndex;
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
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function prepare()
    {
        // nothing to prepare for this module
    }
}
