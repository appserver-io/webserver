<?php
/**
 * \TechDivision\WebServer\Modules\DirectoryModule
 *
 * PHP version 5
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace TechDivision\WebServer\Modules;

use TechDivision\Http\HttpProtocol;
use TechDivision\Http\RequestInterface;
use TechDivision\Http\ResponseInterface;
use TechDivision\WebServer\Interfaces\ModuleInterface;
use TechDivision\WebServer\Modules\ModuleException;

/**
 * Class DirectoryModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class DirectoryModule implements ModuleInterface
{

    public function __construct(RequestInterface $request, ResponseInterface $response)
    {

    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Implement's module logic
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     *
     * @return bool
     * @throws \TechDivision\WebServer\Modules\ModuleException
     */
    public function process(RequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
        $request = $this->getRequest();
        $response = $this->getResponse();
        // get uri
        $uri = $request->getUri();
        // get read path to requested uri
        $realPath = $request->getRealPath();
        // get info about real path.
        $fileInfo = new \SplFileInfo($realPath);
        // check if it's a dir
        if ($fileInfo->isDir() || $uri === '/') {
            // check if uri has trailing slash
            if (substr($uri, -1) !== '/') {
               // set enhance uri with trailing slash to response
               $response->addHeader(HttpProtocol::HEADER_LOCATION, $uri . '/');
               // send redirect status
               $response->setStatusCode(301);
            } else {
                // check if defined index files are found in directory
                if (file_exists($realPath . 'index.html')) {
                    $request->setUri($uri . 'index.html');
                }
            }
        }
        return true;
    }

}