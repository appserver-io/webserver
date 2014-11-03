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

use TechDivision\Connection\ConnectionRequestInterface;
use TechDivision\Connection\ConnectionResponseInterface;
use TechDivision\Http\HttpProtocol;
use TechDivision\Http\HttpResponseStates;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\Server\Dictionaries\ModuleHooks;
use TechDivision\Server\Dictionaries\ServerVars;
use TechDivision\Server\Dictionaries\ModuleVars;
use TechDivision\Server\Interfaces\ModuleInterface;
use TechDivision\Server\Interfaces\RequestContextInterface;
use TechDivision\Server\Interfaces\ServerContextInterface;
use TechDivision\Server\Exceptions\ModuleException;
use TechDivision\Server\Dictionaries\MimeTypes;

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
     * Defines the module name.
     *
     * @var string
     */
    const MODULE_NAME = 'core';

    /**
     * Holds the server context instance
     *
     * @var \TechDivision\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Holds an array of all locations.
     *
     * @var array
     */
    protected $locations;

    /**
     * Expands request context on given request constellation (uri) based on file handler configuration
     *
     * @param \TechDivision\Server\Interfaces\RequestContextInterface $requestContext The request context instance
     *
     * @return void
     */
    public function populateRequestContext(RequestContextInterface $requestContext)
    {

        // get local refs
        $serverContext = $this->getServerContext();

        // get document root
        $documentRoot = $requestContext->getServerVar(ServerVars::DOCUMENT_ROOT);
        // get handlers
        $handlers = $serverContext->getServerConfig()->getHandlers();

        // get uri without querystring
        // Just make sure that you check for the existence of the query string first, as it might not be set
        $uriWithoutQueryString = parse_url($requestContext->getServerVar(ServerVars::X_REQUEST_URI), PHP_URL_PATH);

        // split all path parts got from uri without query string
        $pathParts = explode('/', $uriWithoutQueryString);

        // init vars for path parsing
        $possibleValidPathExtension = '';
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
                    // check if its not a existing file
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

        // load the locations
        $locations = $this->locations;

        // check if there are some volatile location definitions so use them and override global locations
        if ($requestContext->hasModuleVar(ModuleVars::VOLATILE_LOCATIONS)) {
            $locations = $requestContext->getModuleVar(ModuleVars::VOLATILE_LOCATIONS);
        }

        // process the locations for this request
        foreach ($locations as $location) {
            if (preg_match('/' . $location['condition'] . '/', $uriWithoutQueryString)) {
                // if we find a configured file handler responsible for the path extension of this request
                if (isset($location['handlers']['.' . $possibleValidPathExtension])) {
                    // set/overwrite the default handler
                    $handlers['.' . $possibleValidPathExtension] = $location['handlers']['.' . $possibleValidPathExtension];
                    break;
                }
            }
        }

        // set special server var for requested file
        $requestContext->setServerVar(ServerVars::REQUEST_FILENAME, $documentRoot . $possibleValidPath);

        // check if requested file is on filesystem and set it to be valid script filename
        if ($scriptFilename) {
            $requestContext->setServerVar(ServerVars::SCRIPT_FILENAME, $scriptFilename);
            // set specific server vars
            $requestContext->setServerVar(ServerVars::SCRIPT_NAME, $scriptName);
        }

        // if path info is set put it into server vars
        if (strlen($pathInfo) > 0) {
            // set path info vars
            $requestContext->setServerVar(ServerVars::PATH_INFO, $pathInfo);
            $requestContext->setServerVar(ServerVars::PATH_TRANSLATED, $documentRoot . $pathInfo);
        }

        // check if file handler is defined for that script and expand request context
        if (isset($handlers['.' . $possibleValidPathExtension])) {

            // set the file handler to use for modules being able to react on this setting
            $requestContext->setServerVar(
                ServerVars::SERVER_HANDLER,
                $handlers['.' . $possibleValidPathExtension]['name']
            );

            // if file handler params are given, set them as module var
            if (isset($handlers['.' . $possibleValidPathExtension]['params'])) {
                $requestContext->setModuleVar(
                    ModuleVars::VOLATILE_FILE_HANDLER_VARIABLES,
                    $handlers['.' . $possibleValidPathExtension]['params']
                );
            }
        }
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
        // So we have to declare the right ones afterwards...

        /** @var $request \TechDivision\Http\HttpRequestInterface */
        /** @var $response \TechDivision\Http\HttpResponseInterface */

        // if false hook is comming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }

        // check if core module should still handle this request
        // maybe later on this can be overwritten by another core module for some reasons
        if ($requestContext->getServerVar(ServerVars::SERVER_HANDLER) !== self::MODULE_NAME) {
            // stop processing
            return;
        }

        // populates request context for possible script calling based on file handler configurations
        $this->populateRequestContext($requestContext);

        // check if file handler is not core module anymore
        if ($requestContext->getServerVar(ServerVars::SERVER_HANDLER) !== self::MODULE_NAME) {
            // stop processing
            return;
        }

        // if existing file should be served
        if ($requestContext->hasServerVar(ServerVars::SCRIPT_FILENAME)) {

            $scriptFilename = $requestContext->getServerVar(ServerVars::SCRIPT_FILENAME);

            // get file info
            $fileInfo = new \SplFileInfo($scriptFilename);

            // build etag
            $eTag = sprintf('"%x-%x-%x"', $fileInfo->getInode(), $fileInfo->getSize(), (float)str_pad($fileInfo->getMTime(), 16, '0'));

            // set last modified header
            $response->addHeader(HttpProtocol::HEADER_LAST_MODIFIED, gmdate(DATE_RFC822, $fileInfo->getMTime()));

            // set etag header
            $response->addHeader(HttpProtocol::HEADER_ETAG, $eTag);

            // set correct mimetype header
            $response->addHeader(
                HttpProtocol::HEADER_CONTENT_TYPE,
                MimeTypes::getMimeTypeByExtension($fileInfo->getExtension())
            );

            // caching checks
            if (($request->hasHeader(HttpProtocol::HEADER_IF_NONE_MATCH)) &&
                ($request->getHeader(HttpProtocol::HEADER_IF_NONE_MATCH) === $eTag)
            ) {
                // set not modified status without content
                $response->setStatusCode(304);
            } else {
                // serve file by set body stream to file descriptor stream
                $response->setBodyStream(fopen($scriptFilename, "r"));
            }

            // set response state to be dispatched after this without calling other modules process
            $response->setState(HttpResponseStates::DISPATCH);

            // if we got here its maybe a directory index surfing request if $validDir is same as uri
            // todo: implement directory index view and surfing

        } else {
            // for now we will throw a 404 as well here for non existing index files in directory
            throw new ModuleException(
                sprintf(
                    "The requested URL %s was not found on this server.",
                    parse_url($requestContext->getServerVar(ServerVars::X_REQUEST_URI), PHP_URL_PATH)
                ),
                404
            );
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
     * @param \TechDivision\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
        $this->serverContext = $serverContext;
        $this->locations = $serverContext->getServerConfig()->getLocations();
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
     * @return \TechDivision\Server\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
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
