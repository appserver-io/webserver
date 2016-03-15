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
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules;

use AppserverIo\Http\HttpResponseStates;
use AppserverIo\Psr\HttpMessage\Protocol;
use AppserverIo\Psr\HttpMessage\RequestInterface;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\WebServer\Interfaces\HttpModuleInterface;

/**
 * Module that creates a directory index, if no other module handles
 * the request. If you want to use this module, the modules have to be
 * configured in the following order:
 *
 * <module type="\AppserverIo\WebServer\Modules\VirtualHostModule"/>
 * <module type="\AppserverIo\WebServer\Modules\AuthenticationModule"/>
 * <module type="\AppserverIo\WebServer\Modules\EnvironmentVariableModule" />
 * <module type="\AppserverIo\WebServer\Modules\RewriteModule"/>
 * <module type="\AppserverIo\WebServer\Modules\DirectoryModule"/>
 * <module type="\AppserverIo\WebServer\Modules\AccessModule"/>
 * <module type="\AppserverIo\WebServer\Modules\LocationModule"/>
 * <module type="\AppserverIo\WebServer\Modules\AutoIndexModule"/>
 * <module type="\AppserverIo\WebServer\Modules\CoreModule"/>
 * <module type="\AppserverIo\WebServer\Modules\PhpModule"/>
 * <module type="\AppserverIo\WebServer\Modules\FastCgiModule"/>
 * <module type="\AppserverIo\Appserver\ServletEngine\ServletEngine" />
 *
 * @author    Tim Wagner <tw@appserver.io>
 * @author    Johann Zelger <jz@appserver.io>
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
     * Initiates the module.
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
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

        // make request context available for usage in template
        $this->requestContext = $requestContext;

        // query whether the auto index module is available
        if ($this->getRequestContext()->hasServerVar(ServerVars::SERVER_AUTO_INDEX) === false) {
            return;
        }

        // query whether the auto index module is available and enabled
        if ($this->getRequestContext()->getServerVar(ServerVars::SERVER_AUTO_INDEX) === ServerVars::VALUE_AUTO_INDEX_OFF) {
            return;
        }

        // stop processing if file handler will not be core in case that location module
        // has changed the server handler to be proxy, fastcgi or what ever.
        if ($requestContext->getServerVar(ServerVars::SERVER_HANDLER) !== 'core') {
            return;
        }

        // now load the URL without path information and query string
        $url = $this->getUrl();

        // query whether the URL ends with a slash
        if ($url[strlen($url) - 1] !== '/') {
            return;
        }

        // query whether an existing path is requested
        if (is_dir($realPath = $this->getRealPath()) === false) {
            return;
        }

        // load the auto index template if available
        $autoIndexTemplatePath = $this->getRequestContext()->getServerVar(ServerVars::SERVER_AUTO_INDEX_TEMPLATE_PATH);

        // query whether a template is configured and available
        if ($autoIndexTemplatePath && is_file($autoIndexTemplatePath)) {
            // render errors page
            ob_start();
            require $autoIndexTemplatePath;
            $autoIndexPage = ob_get_clean();

        } else {
            // initialize the directory listing content
            $directoryListing = '<tr><th>Name</th><th>Last Modified</th><th>Size</th></tr>';

            // query whether if we've parent directory or not
            if ($this->hasParent($realPath)) {
                $directoryListing .= sprintf(
                    '<tr><td colspan="3"><a href="%s">Parent Directory</a></td></tr>',
                    $this->getParentLink()
                );
            }

            // append the found files + directories to the directory listing
            foreach ($this->getDirectoryContent($realPath) as $directory) {
                // append the file or directory to the directory listing
                $directoryListing .= sprintf(
                    '<tr><td><a href="%s">%s</a></td><td>%s</td><td>%d</td></tr>',
                    $this->getLink($directory),
                    $this->getName($directory),
                    $this->getDate($directory),
                    $this->getFilesize($directory)
                );
            }

            // concatenate the elements of the auto index page
            $autoIndexPage = sprintf(
                '<!DOCTYPE html><html><head><title>Index of %s</title></head><body><h1>Index of %s</h1><table>%s</table></body></html>',
                $this->getUri(),
                $this->getUri(),
                $directoryListing
            );
        }

        // append errors page to response body
        $response->appendBodyStream($autoIndexPage);

        // set the Content-Type to text/html
        $response->addHeader(Protocol::HEADER_CONTENT_TYPE, 'text/html');

        // set response state to be dispatched after this without calling other modules process
        $response->setState(HttpResponseStates::DISPATCH);
    }

    /**
     * Returns the actual request context instance.
     *
     * @return \AppserverIo\Server\Interfaces\RequestContextInterface The actual requests context instance
     */
    public function getRequestContext()
    {
        return $this->requestContext;
    }

    /**
     * Returns the absolute path for the acutal request URL.
     *
     * @return string The absolute path for the acutal request URL
     */
    public function getRealPath()
    {
        return realpath($this->getDocumentRoot() . $this->getUrl());
    }

    /**
     * Returns an array with the content of the passed directory.
     *
     * @param string $realPath The absolute path for the acutal request URL
     *
     * @return array The content of the actual directory
     */
    public function getDirectoryContent($realPath)
    {
        return glob(sprintf('%s/*', $realPath));
    }

    /**
     * Returns the actual, by previous modules pre-processed, request URI.
     *
     * @return string The actual pre-processed request URI
     */
    public function getUri()
    {
        return $this->getRequestContext()->getServerVar(ServerVars::X_REQUEST_URI);
    }

    /**
     * Returns the document root for the actual request.
     *
     * @return string The document root for the actual request
     */
    public function getDocumentRoot()
    {
        return $this->getRequestContext()->getServerVar(ServerVars::DOCUMENT_ROOT);
    }

    /**
     * Returns the acutal request URL.
     *
     * @return string The actual request URL
     */
    public function getUrl()
    {
        return parse_url($this->getUri(), PHP_URL_PATH);
    }

    /**
     * Queries whether the passed path has a parent directory we want to render.
     *
     * @param string $realPath The absolute path to query for a parent directory
     *
     * @return boolean TRUE if we've a parent directory, else FALSE
     */
    public function hasParent($realPath)
    {
        return $this->getDocumentRoot() !== $realPath;
    }

    /**
     * Returns the relative path to the parent directory for the actual request URL.
     *
     * @return string the relative path to the parent directory
     */
    public function getParentLink()
    {
        return dirname($this->getUrl());
    }

    /**
     * Returns the relative path to the documention root for the passed directory.
     *
     * @param string $directory The directory to return the relative path to the document root for
     *
     * @return mixed The relative path to the document root for the passed directory
     */
    public function getIcon($directory)
    {
        if (is_file($directory)) {
            return pathinfo($directory, PATHINFO_EXTENSION);
        }
        // if it not a file it should be an directory
        return 'dir';
    }

    /**
     * Returns the relative path to the documention root for the passed directory.
     *
     * @param string $directory The directory to return the relative path to the document root for
     *
     * @return mixed The relative path to the document root for the passed directory
     */
    public function getLink($directory)
    {
        return str_replace($this->getDocumentRoot(), '', $directory);
    }

    /**
     * Returns the name of last element of the passed directory.
     *
     * @param string $directory The directory to return the last element from
     *
     * @return string The name of the last directory element
     */
    public function getName($directory)
    {
        return basename($directory);
    }

    /**
     * Returns the modification time of the last element of the passed directory.
     *
     * @param string $directory The directory to return the last elements modification date from
     *
     * @return string The modification date of the last directory element
     */
    public function getDate($directory)
    {
        return date('Y-m-d H:i:s', filemtime($directory));
    }

    /**
     * Returns the filesize of the last element of the passed directory.
     *
     * @param string $directory The directory to return the last elements filesize from
     *
     * @return string The filesize of the last directory element
     */
    public function getFilesize($directory)
    {
        $bytes = filesize($directory);
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        // return formated bytes
        return $bytes;
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
