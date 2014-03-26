<?php
/**
 * \TechDivision\WebServer\Modules\AccessModule
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
use TechDivision\WebServer\Dictionaries\ServerVars;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\WebServer\Interfaces\ModuleInterface;
use TechDivision\WebServer\Exceptions\ModuleException;
use TechDivision\WebServer\Interfaces\ServerContextInterface;

/**
 * Class AccessModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class AccessModule implements ModuleInterface
{

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'access';

    /**
     * Hold's the server context instance
     *
     * @var \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Hold's an array of all accesses
     *
     * @var array
     */
    protected $accesses;

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
     * Return's the server context instance
     *
     * @return \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
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
        $this->accesses = $serverContext->getServerConfig()->getAccesses();

    }

    /**
     * Implement's module logic
     *
     * @param \TechDivision\Http\HttpRequestInterface  $request  The request object
     * @param \TechDivision\Http\HttpResponseInterface $response The response object
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function process(HttpRequestInterface $request, HttpResponseInterface $response)
    {
        // set req and res object internally
        $this->request = $request;
        $this->response = $response;

        // get ref to local var
        $serverContext = $this->getServerContext();

        // generally everything is not allowed
        $allowed = false;

        // check allow accesses informations if something matches
        foreach ($this->accesses['allow'] as $accessData) {

            // we are optimistic an initial say data will match
            $matchAllow = true;

            // check if accessData matches server vars
            foreach ($accessData as $serverVar => $varPattern) {

                // check if server var exists
                if ($serverContext->hasServerVar($serverVar)) {
                    // check if pattern matches
                    if (!preg_match(
                        '/' . $varPattern . '/',
                        $serverContext->getServerVar($serverVar)
                    )) {
                        $matchAllow = false;
                        // break here if anything not matches
                        break;
                    }
                }
            }

            if ($matchAllow) {
                // set allowed flag true
                $allowed = true;
                // break here cause' we found an allowed access
                break;
            }
        }

        // check deny accesses informations if something matches
        foreach ($this->accesses['deny'] as $accessData) {

            // initial nothing denies the request
            $matchDeny = true;

            // check if accessData matches server vars
            foreach ($accessData as $serverVar => $varPattern) {

                // check if server var exists
                if ($serverContext->hasServerVar($serverVar)) {
                    // check if pattern matches
                    if (!preg_match(
                        '/' . $varPattern . '/',
                        $serverContext->getServerVar($serverVar)
                    )) {
                        $matchDeny = false;
                        // break here if anything not matches
                        break;
                    }
                }
            }

            if ($matchDeny) {
                // set allowed flag true
                $allowed = false;
                // break here cause' we found an allowed access
                break;
            }
        }

        // check if it's finally allowed
        if (!$allowed) {
            throw new ModuleException('This request is forbidden', 403);
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
}
