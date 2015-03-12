<?php

/**
 * \AppserverIo\WebServer\Modules\AutoIndexModule
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules;

use AppserverIo\Http\HttpProtocol;
use AppserverIo\Http\HttpResponseStates;
use AppserverIo\Psr\HttpMessage\RequestInterface;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\Psr\HttpMessage\Protocol;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\WebServer\Interfaces\HttpModuleInterface;

/**
 * Module that creates a directory index, if no other module handles
 * the request.
 *
 * Modules have to be configured in the following order:
 *
 * <module type="\AppserverIo\WebServer\Modules\VirtualHostModule"/>
 * <module type="\AppserverIo\WebServer\Modules\AuthenticationModule"/>
 * <module type="\AppserverIo\WebServer\Modules\EnvironmentVariableModule" />
 * <module type="\AppserverIo\WebServer\Modules\RewriteModule"/>
 * <module type="\AppserverIo\WebServer\Modules\DirectoryModule"/>
 * <module type="\AppserverIo\WebServer\Modules\AccessModule"/>
 * <module type="\AppserverIo\WebServer\Modules\AutoIndexModule"/>
 * <module type="\AppserverIo\WebServer\Modules\CoreModule"/>
 * <module type="\AppserverIo\WebServer\Modules\PhpModule"/>
 * <module type="\AppserverIo\WebServer\Modules\FastCgiModule"/>
 * <module type="\AppserverIo\Appserver\ServletEngine\ServletEngine" />
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class AutoIndexModule implements HttpModuleInterface
{

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'autoIndex';

    /**
     * Holds the server context instance
     *
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

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
     * Return's the server context instance
     *
     * @return \AppserverIo\Server\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
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

        // In php an interface is, by definition, a fixed contract. It is immutable.
        // So we have to declare the right ones afterwards...
        /**
         * @var $request \AppserverIo\Psr\HttpMessage\RequestInterface
         */
        /**
         * @var $response \AppserverIo\Psr\HttpMessage\ResponseInterface
         */

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

        // get read path to requested uri
        $realPath = $documentRoot . $url;

        // check if it's a dir
        if (is_dir($realPath)) {

            // initialize the directory listing
            $directoryListing = '<tr><th>Name</th><th>Last Modified</th><th>Size</th></tr>';

            // query whether if we've parent directory or not
            if ($realPath !== $url) {
                $directoryListing .= sprintf(
                    '<tr><td colspan="3"><a href="%s">Parent Directory</a></td></tr>',
                    dirname($url)
                );
            }

            // append the found files + directories to the directory listing
            foreach (glob(sprintf('%s*', $realPath)) as $directory) {

                // prepare the relative path the file
                $relativePath = str_replace($documentRoot, '', $directory);

                // append the file or directory to the directory listing
                $directoryListing .= sprintf(
                    '<tr><td><a href="%s">%s</a></td><td>%s</td><td>%d</td></tr>',
                    $relativePath,
                    basename($directory),
                    date('Y-m-d H:i:s', filemtime($directory)),
                    filesize($directory)
                );
            }

            // append header and directory listing to response
            $response->addHeader(HttpProtocol::HEADER_CONTENT_TYPE, 'text/html');
            $response->appendBodyStream(
                sprintf(
                    '<!DOCTYPE html><html><head><title>Index of %s</title></head><body><h1>Index of %s</h1><table>%s</table></body></html>',
                    $requestContext->getServerVar(ServerVars::REQUEST_URI),
                    $requestContext->getServerVar(ServerVars::REQUEST_URI),
                    $directoryListing
                )
            );

            // set response state to be dispatched after this without calling other modules process
            $response->setState(HttpResponseStates::DISPATCH);
        }
    }

    /**
     * Renders directory listing page by given directory
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
        if (($errorsPageTemplatePath = $this->getRequestContext()->getServerVar(ServerVars::SERVER_ERRORS_PAGE_TEMPLATE_PATH)) && is_file($errorsPageTemplatePath)) {
            // render errors page
            ob_start();
            require $errorsPageTemplatePath;
            $errorsPage = ob_get_clean();
        } else {
            // build up error message manually without template
            $errorsPage = $response->getStatusCode() . ' ' . $response->getStatusReasonPhrase() . PHP_EOL . PHP_EOL . $errorMessage . PHP_EOL . PHP_EOL . strip_tags($this->getRequestContext()->getServerVar(ServerVars::SERVER_SIGNATURE));
        }
        // append errors page to response body
        $response->appendBodyStream($errorsPage);
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
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function prepare()
    {
        // nothing to prepare for this module
    }
}
