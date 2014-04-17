<?php
/**
 * \TechDivision\WebServer\Modules\FastCgiModule
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
 * @author     Tim Wagner <tw@techdivision.com>
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
use TechDivision\WebServer\Dictionaries\ModuleHooks;
use TechDivision\WebServer\Interfaces\ModuleInterface;
use TechDivision\WebServer\Exceptions\ModuleException;
use TechDivision\WebServer\Interfaces\ServerContextInterface;

use Crunch\FastCGI\Client;

/**
 * Class CoreModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class FastCgiModule implements ModuleInterface
{
    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'fastcgi';

    /**
     * Hold's the server context instance
     *
     * @var \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Implement's module logic for given hook
     *
     * @param \TechDivision\Http\HttpRequestInterface  $request  The request object
     * @param \TechDivision\Http\HttpResponseInterface $response The response object
     * @param int                                      $hook     The current hook to process logic for
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function process(HttpRequestInterface $request, HttpResponseInterface $response, $hook)
    {
        // if false hook is comming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }
        
        $serverContext = $this->getServerContext();

        // check if server handler sais php modules should react on this request as file handler
        if ($serverContext->getServerVar(ServerVars::SERVER_HANDLER) === self::MODULE_NAME) {

            // check if file does not exist
            if (!$serverContext->hasServerVar(ServerVars::SCRIPT_FILENAME)) {
                // send 404
                $response->setStatusCode(404);
                throw new ModuleException(null, 404);
            }
        
            $fastCgi = new Client('127.0.0.1', 9000);
            $connection = $fastCgi->connect();
            
            $fastCgiRequest = $connection->newRequest();
            
            $fastCgiRequest->parameters = array(
                ServerVars::GATEWAY_INTERFACE => 'FastCGI/1.0',
                ServerVars::REQUEST_METHOD    => $serverContext->getServerVar(ServerVars::REQUEST_METHOD),
                ServerVars::SCRIPT_FILENAME   => $serverContext->getServerVar(ServerVars::SCRIPT_FILENAME),
                ServerVars::QUERY_STRING      => $serverContext->getServerVar(ServerVars::QUERY_STRING),
                ServerVars::SCRIPT_NAME       => $serverContext->getServerVar(ServerVars::SCRIPT_NAME),
                ServerVars::REQUEST_URI       => $serverContext->getServerVar(ServerVars::REQUEST_URI),
                ServerVars::DOCUMENT_ROOT     => $serverContext->getServerVar(ServerVars::DOCUMENT_ROOT),
                ServerVars::SERVER_PROTOCOL   => $serverContext->getServerVar(ServerVars::SERVER_PROTOCOL),
                ServerVars::HTTPS             => $serverContext->getServerVar(ServerVars::HTTPS),
                ServerVars::SERVER_SOFTWARE   => $serverContext->getServerVar(ServerVars::SERVER_SOFTWARE),
                ServerVars::REMOTE_ADDR       => $serverContext->getServerVar(ServerVars::REMOTE_ADDR),
                ServerVars::REMOTE_PORT       => $serverContext->getServerVar(ServerVars::REMOTE_PORT),
                ServerVars::SERVER_ADDR       => $serverContext->getServerVar(ServerVars::SERVER_ADDR),
                ServerVars::SERVER_PORT       => $serverContext->getServerVar(ServerVars::SERVER_PORT),
                ServerVars::SERVER_NAME       => $serverContext->getServerVar(ServerVars::SERVER_NAME),
                'CONTENT_TYPE'                => $request->getHeader(HttpProtocol::HEADER_CONTENT_TYPE),
                'DOCUMENT_URI'                => ''
            );
            
            if ($serverContext->hasServerVar(ServerVars::REDIRECT_STATUS)) {
                $fastCgiRequest->parameters[ServerVars::REDIRECT_STATUS] = $serverContext->getServerVar(ServerVars::REDIRECT_STATUS);
            }
            
            if ($request->hasHeader(HttpProtocol::HEADER_CONTENT_LENGTH)) {
                $fastCgiRequest->parameters['CONTENT_LENGTH'] = strlen($bodyContent = $request->getBodyContent());
                $fastCgiRequest->stdin = $bodyContent;
            }
            
            foreach ($request->getHeaders() as $key => $value) {
                $fastCgiRequest->parameters['HTTP_' . str_replace('-', '_', strtoupper($key))] = $value;
            }
            
            $fastCgiResponse = $connection->request($fastCgiRequest);
            
            $separator = "\r\n";
            $exploded = explode($separator, $fastCgiResponse->content);
            
            if ($firstLine = reset($exploded)) {

                // extract header info
                $extractedHeaderInfo = explode(': ', trim($firstLine));
                if (!$extractedHeaderInfo) {
                    throw new HttpException('Wrong header format');
                }
                
                // split name and value
                list ($headerName, $headerValue) = $extractedHeaderInfo;
                
                // normalize header names in case of 'Content-type' into 'Content-Type'
                $headerName = str_replace(' ', '-', ucwords(str_replace('-', ' ', $headerName)));
                
                if ($headerName === HttpProtocol::HEADER_STATUS) {
                    $response->setStatus($headerValue);
                } else {
                    $response->addHeader($headerName, $headerValue);
                }

                while ($line = next($exploded)) {
                
                    if ($line == $separator) {
                        break;
                    }
                
                    // extract header info
                    $extractedHeaderInfo = explode(': ', trim($line));
                    if (!$extractedHeaderInfo) {
                        throw new HttpException('Wrong header format');
                    }
                
                    // split name and value
                    list ($headerName, $headerValue) = $extractedHeaderInfo;
                
                    // normalize header names in case of 'Content-type' into 'Content-Type'
                    $headerName = str_replace(' ', '-', ucwords(str_replace('-', ' ', $headerName)));
                
                    if ($headerName === HttpProtocol::HEADER_STATUS) {
                        $response->setStatus($headerValue);
                    } else {
                        $response->addHeader($headerName, $headerValue);
                    }
                }
                
                $messageBody = '';
                while ($line = next($exploded)) {
                    $messageBody .= $line;
                }
                
                $response->appendBodyStream($messageBody);
                
            }

            // set response state to be dispatched after this without calling other modules process
            $response->setState(HttpResponseStates::DISPATCH);
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
     * Return's the server context instance
     *
     * @return \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }
}
