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
use TechDivision\WebServer\Modules\ModuleException;
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
     * Holds the requested filename
     *
     * @var string
     */
    protected $requestedFilename;

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
        // set local var
        $serverContext = $this->getServerContext();

        // todo: read out config for all file handle extensions not just hardcore php
        $fileHandlerExtension = '.php';

        // check if fileHandler type are present in uri
        if (strpos($request->getUri(), $fileHandlerExtension) !== false) {
            // get document root
            $documentRoot = $serverContext->getServerVar(ServerVars::DOCUMENT_ROOT);
            // check where the script position ends
            $scriptStrEndPos = strpos($request->getUri(), $fileHandlerExtension) + strlen($fileHandlerExtension);
            // parse script name
            $scriptName = substr($request->getUri(), 0, $scriptStrEndPos);
            // set script name to request object
            $request->setScriptName($scriptName);

            $serverContext->setServerVar(
                ServerVars::SCRIPT_NAME,
                $request->getScriptName()
            );
            $serverContext->setServerVar(
                ServerVars::SCRIPT_FILENAME,
                $documentRoot . $request->getScriptName()
            );

            // parse path info if exists in uri
            if (($pathInfo = substr(str_replace('?' . $request->getQueryString(), '', $request->getUri()), $scriptStrEndPos)) !== false) {
                // set path info to request object
                $request->setPathInfo($pathInfo);

                $serverContext->setServerVar(
                    ServerVars::PATH_INFO,
                    $request->getPathInfo()
                );
                $serverContext->setServerVar(
                    ServerVars::PATH_TRANSLATED,
                    $documentRoot . $request->getPathInfo()
                );
            }

            /**
             * it's intended to not set ScriptFilename and PathTranslated here because the request doesn't
             * care about document root stuff. so this is set by the connection handler!
             */
            // todo: check and implement ORIG_PATH_INFO server var
        } else {

            // set requested filename
            $this->setRequestedFilename($request->getRealPath());

            // check if file exists on filesystem
            if (file_exists($this->getRequestedFilename())) {
                // set body stream to file descriptor stream
                $response->setBodyStream(fopen($this->getRequestedFilename(), 'r'));
                // set correct mimetype header
                $response->addHeader(
                    HttpProtocol::HEADER_CONTENT_TYPE,
                    MimeTypes::getMimeTypeByFilename($this->getRequestedFilename())
                );
                // set response state to be dispatched after this without calling other modules process
                $response->setState(HttpResponseStates::DISPATCH);
            }
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
