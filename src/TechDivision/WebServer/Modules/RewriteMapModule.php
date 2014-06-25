<?php
/**
 * \TechDivision\WebServer\Modules\RewriteMapModule
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
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\Http\HttpResponseStates;
use TechDivision\Server\Dictionaries\ModuleHooks;
use TechDivision\Server\Dictionaries\ModuleVars;
use TechDivision\Server\Interfaces\ModuleInterface;
use TechDivision\Server\Exceptions\ModuleException;
use TechDivision\Server\Interfaces\ServerContextInterface;
use TechDivision\Server\Dictionaries\ServerVars;

/**
 * Class RewriteMapModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class RewriteMapModule implements ModuleInterface
{
    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'rewriteMap';

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
        $this->rewriteMaps = $serverContext->getServerConfig()->getRewriteMaps();
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
     * @param \TechDivision\Http\HttpRequestInterface  $request  The request object
     * @param \TechDivision\Http\HttpResponseInterface $response The response object
     * @param int                                      $hook     The current hook to process logic for
     *
     * @return bool
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function process(HttpRequestInterface $request, HttpResponseInterface $response, $hook)
    {
        // if false hook is comming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }

        // set req and res object internally
        $this->request = $request;
        $this->response = $response;
        // get server context ref to local func
        $serverContext = $this->getServerContext();

        // get default rewrite maps definitions
        $rewriteMaps = $this->rewriteMaps;

        // check if there are some volatile rewrite map definitions so add them
        if ($serverContext->hasModuleVar(ModuleVars::VOLATILE_REWRITE_MAPS)) {
            $volatileRewriteMaps = $serverContext->getModuleVar(ModuleVars::VOLATILE_REWRITE_MAPS);
            // merge rewrite maps
            $rewriteMaps = array_merge(
                $volatileRewriteMaps,
                $this->rewriteMaps
            );
        }

        // check protocol to be either http or https when secure is going on
        $protocol = 'http://';
        if ($serverContext->getServerVar(ServerVars::HTTPS) === ServerVars::VALUE_HTTPS_ON) {
            $protocol = 'https://';
        }

        // get clean request path without query string etc...
        $requestPath = parse_url($serverContext->getServerVar(ServerVars::X_REQUEST_URI), PHP_URL_PATH);

        // init all rewrite mappers by types and do look up
        foreach ($rewriteMaps as $rewriteMapType => $rewriteMapParams) {

            // Include the requested hostname as a param, some mappers might need it
            $rewriteMapParams['headerHost'] = $request->getHeader(HttpProtocol::HEADER_HOST);
            // Same for the protocol
            $rewriteMapParams['protocol'] = $protocol;

            // Get ourselves a rewriteMapper of the right type
            $rewriteMapper = new $rewriteMapType($rewriteMapParams);
            // lookup by request path
            if ($targetUrl = $rewriteMapper->lookup($requestPath)) {

                // set enhance uri to response
                $response->addHeader(HttpProtocol::HEADER_LOCATION, $targetUrl);
                // send redirect status
                $response->setStatusCode(301);
                // add header to be sure that is was us
                $response->addHeader('X-Rewritten-By', __CLASS__);
                // set response state to be dispatched after this without calling other modules process
                $response->setState(HttpResponseStates::DISPATCH);

                // We found something, stop the loop
                break;
            }
        }
        return true;
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
