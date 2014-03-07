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
            $uriWithoutQueryString = str_replace('?' . $request->getQueryString(), '', $request->getUri());
            // split all path parts got from uri without query string
            $pathParts = explode('/', $uriWithoutQueryString);
            // init vars for path parsing
            $possibleValidPath = '';
            $pathInfo = '';
            $validDir = null;
            $scriptName = null;

            // iterate through all dirs beginning at 1 because 0 is always empty in this case
            for ($i = 1; $i < count($pathParts); ++$i) {
                // check if no script name was found yet
                if (!$scriptName) {
                    // append valid path
                    $possibleValidPath .= DIRECTORY_SEPARATOR . $pathParts[$i];
                    // check if dir does not exists
                    if (!is_dir($documentRoot . $possibleValidPath)) {
                        // check if its not a file too
                        if (!is_file($documentRoot . $possibleValidPath)) {
                            // maybe there is a special request going on. break here an go on processing to let other
                            // modules react on this uri if some virtual file handling was registered.
                            break;
                        }
                        // at this point it's def. an existing file in an existing dir. our script name
                        $scriptName = $possibleValidPath;
                    } else {
                        $validDir = $possibleValidPath;
                    }
                } else {
                    // else build up path info
                    $pathInfo .= DIRECTORY_SEPARATOR . $pathParts[$i];
                }
            }

            // check if possibleValidPath has an extension so it could be a file
            $possibleValidPathExtension = pathinfo($possibleValidPath, PATHINFO_EXTENSION);

            // if extension was found so a possible filename to server was found
            if (strlen($possibleValidPathExtension) > 0) {

                // if it's really a file
                if ($scriptName) {
                    // set script name and script filename to server vars
                    $serverContext->setServerVar(ServerVars::SCRIPT_NAME, $scriptName);
                    $serverContext->setServerVar(ServerVars::SCRIPT_FILENAME, $documentRoot . $scriptName);
                    // if path info is set put it into server vars
                    if (strlen($pathInfo) > 0) {
                        // todo: check and implement ORIG_PATH_INFO server var
                        $serverContext->setServerVar(ServerVars::PATH_INFO, $pathInfo);
                        $serverContext->setServerVar(ServerVars::PATH_TRANSLATED, $documentRoot . $pathInfo);
                    }
                    // get script extension
                    $scriptExtension = pathinfo($scriptName, PATHINFO_EXTENSION);

                    // check if no other file handler was registered to server that existing file
                    if (!isset($handlers['.' . $scriptExtension])) {
                        // set body stream to file descriptor stream
                        $response->setBodyStream(fopen($documentRoot . $scriptName, 'r'));
                        // set correct mimetype header
                        $response->addHeader(
                            HttpProtocol::HEADER_CONTENT_TYPE,
                            MimeTypes::getMimeTypeByExtension($scriptExtension)
                        );
                        // set response state to be dispatched after this without calling other modules process
                        $response->setState(HttpResponseStates::DISPATCH);
                        // go out
                        return;
                    }
                }

                // if a file handler was requested for this possible extension
                if (isset($handlers['.' . $possibleValidPathExtension])) {
                    // set new handler to use for modules being able to react on this setting
                    $serverContext->setServerVar(
                        ServerVars::SERVER_HANDLER, $handlers['.' . $possibleValidPathExtension]
                    );
                    // go out and let other modules process this request
                    return;
                }

                // at this point there was no one who can deliver that file so its time to throw a 404
                $response->setStatusCode(404);
                throw new ModuleException(null, 404);
            }

            // if we got here its maybe a directory index surfing request if $validDir is same as uri
            // todo: implement directory index view and surfing

            // for now we will throw a 404 as well here for non existing index files in directory
            $response->setStatusCode(404);
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
