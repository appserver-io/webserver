<?php
/**
 * \TechDivision\WebServer\Modules\CoreModule
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
use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\WebServer\Exceptions\ModuleException;
use TechDivision\WebServer\Dictionaries\MimeTypes;

/**
 * Class CoreModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class CoreModule implements ModuleInterface
{

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'core';

    /**
     * Hold's the server context instance
     *
     * @var \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Implement's module logic
     *
     * @param \TechDivision\Http\HttpRequestInterface  $request  The request instance
     * @param \TechDivision\Http\HttpResponseInterface $response The response instance
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function process(HttpRequestInterface $request, HttpResponseInterface $response)
    {
        // set local vars
        $serverContext = $this->getServerContext();

        // check if core module should still handle this request
        // maybe later on this can be overwritten by another core module for some reasons
        if ($serverContext->getServerVar(ServerVars::SERVER_HANDLER) === self::MODULE_NAME) {
            // get document root
            $documentRoot = $serverContext->getServerVar(ServerVars::DOCUMENT_ROOT);
            // get handlers
            $handlers = $serverContext->getServerConfig()->getHandlers();
            // get uri without querystring
            // Just make sure that you check for the existence of the query string first, as it might not be set
            $uriWithoutQueryString = $serverContext->getServerVar(ServerVars::REQUEST_URI);

            if ($serverContext->hasServerVar(ServerVars::QUERY_STRING)) {
                $uriWithoutQueryString = str_replace(
                    '?' . $serverContext->getServerVar(ServerVars::QUERY_STRING),
                    '',
                    $uriWithoutQueryString
                );
            }

            // split all path parts got from uri without query string
            $pathParts = explode('/', $uriWithoutQueryString);
            // init vars for path parsing
            $possibleValidPath = '';
            $pathInfo = '';
            $validDir = null;
            $scriptName = null;
            $scriptFilename = null;

            // note: only if file extension hits a filehandle info it will be possible to set path info etc...

            // iterate through all dirs beginning at 1 because 0 is always empty in this case
            for ($i = 1; $i < count($pathParts); ++$i) {
                // check if no script name was found yet
                if (!$scriptName) {
                    // append valid path
                    $possibleValidPath .= DIRECTORY_SEPARATOR . $pathParts[$i];
                    // get possible extension
                    $possibleValidPathExtension = pathinfo($possibleValidPath, PATHINFO_EXTENSION);
                    // check if dir does not exists
                    if (!is_dir($documentRoot . $possibleValidPath)) {
                        // check if its a existing file
                        if (!is_file($documentRoot . $possibleValidPath)) {
                            // check if file handler is defined for that virtual file
                            if (isset($handlers['.' . $possibleValidPathExtension])) {
                                // set script name for further processing as script aspect
                                $scriptName = $possibleValidPath;
                            }
                        } else {
                            // set script name
                            $scriptName = $possibleValidPath;
                            // set script filename
                            $scriptFilename = $documentRoot . $scriptName;
                        }
                    } else {
                        // save valid dir for indexed surfing later on
                        $validDir = $possibleValidPath;
                    }
                } else {
                    // else build up path info
                    $pathInfo .= DIRECTORY_SEPARATOR . $pathParts[$i];
                }
            }

            // check if file handler is defined for that script
            if (isset($handlers['.' . $possibleValidPathExtension])) {
                // set specific server vars
                $serverContext->setServerVar(ServerVars::SCRIPT_NAME, $scriptName);
                // check if script is on filesystem
                if ($scriptFilename) {
                    $serverContext->setServerVar(ServerVars::SCRIPT_FILENAME, $scriptFilename);
                    // set special server var for existing file for that request
                    $serverContext->setServerVar(ServerVars::REQUEST_FILENAME, $scriptFilename);
                }
                // if path info is set put it into server vars
                if (strlen($pathInfo) > 0) {
                    // set path info vars
                    $serverContext->setServerVar(ServerVars::PATH_INFO, $pathInfo);
                    $serverContext->setServerVar(ServerVars::PATH_TRANSLATED, $documentRoot . $pathInfo);
                }
                // set new handler to use for modules being able to react on this setting
                $serverContext->setServerVar(
                    ServerVars::SERVER_HANDLER,
                    $handlers['.' . $possibleValidPathExtension]
                );

                // go out
                return;

            } else {

                // get file info
                $fileInfo = new \SplFileInfo($documentRoot . $scriptName);

                // build etag
                $eTag = sprintf('"%x-%x-%x"', $fileInfo->getInode(), $fileInfo->getSize(), (float)str_pad($fileInfo->getMTime(), 16, '0'));

                // set last modified header
                $response->addHeader(HttpProtocol::HEADER_LAST_MODIFIED, gmdate(DATE_RFC822, $fileInfo->getMTime()));

                // set etag header
                $response->addHeader(HttpProtocol::HEADER_ETAG, $eTag);
                // set correct mimetype header
                $response->addHeader(
                    HttpProtocol::HEADER_CONTENT_TYPE,
                    MimeTypes::getMimeTypeByExtension($possibleValidPathExtension)
                );

                // caching checks
                if (($request->hasHeader(HttpProtocol::HEADER_IF_NONE_MATCH)) &&
                    ($request->getHeader(HttpProtocol::HEADER_IF_NONE_MATCH) === $eTag)) {
                    // set not modified status without content
                    $response->setStatusCode(304);
                } else {
                    // serve file by set body stream to file descriptor stream
                    $response->setBodyStream(fopen($documentRoot . $scriptName, "r"));
                }

                // set response state to be dispatched after this without calling other modules process
                $response->setState(HttpResponseStates::DISPATCH);

                // go out
                return;
            }

            // if we got here its maybe a directory index surfing request if $validDir is same as uri
            // todo: implement directory index view and surfing

            // for now we will throw a 404 as well here for non existing index files in directory
            throw new ModuleException(null, 404);
        }
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
    }

    /**
     * Set's the requested filename
     *
     * @param string $requestedFilename The requested filename
     *
     * @return void
     */
    public function setRequestedFilename($requestedFilename)
    {
        $this->requestedFilename = $requestedFilename;
    }

    /**
     * Return's the requested filename
     *
     * @return string
     */
    public function getRequestedFilename()
    {
        return $this->requestedFilename;
    }

    /**
     * Return's the server context instance
     *
     * @return \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }
}
