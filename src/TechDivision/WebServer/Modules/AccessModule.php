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

use TechDivision\Connection\ConnectionRequestInterface;
use TechDivision\Connection\ConnectionResponseInterface;
use TechDivision\Http\HttpProtocol;
use TechDivision\Server\Dictionaries\ModuleHooks;
use TechDivision\Server\Dictionaries\ServerVars;
use TechDivision\Server\Dictionaries\ModuleVars;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\Server\Interfaces\ModuleInterface;
use TechDivision\Server\Exceptions\ModuleException;
use TechDivision\Server\Interfaces\RequestContextInterface;
use TechDivision\Server\Interfaces\ServerContextInterface;

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
     * @var \TechDivision\Server\Interfaces\ServerContextInterface
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
     * @return \TechDivision\Server\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
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
        $this->accesses = $serverContext->getServerConfig()->getAccesses();
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
        // So we have to declair the right ones afterwards...
        /** @var $request \TechDivision\Http\HttpRequestInterface */
        /** @var $response \TechDivision\Http\HttpResponseInterface */

        // if false hook is comming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }

        // set req and res object internally
        $this->request = $request;
        $this->response = $response;

        // get default access definitions
        $accesses = $this->accesses;

        // check if there are some volatile access definitions so use them and override global accesses
        if ($requestContext->hasModuleVar(ModuleVars::VOLATILE_ACCESSES)) {
            // reset by volatile accesses
            $accesses = $requestContext->getModuleVar(ModuleVars::VOLATILE_ACCESSES);
        }

        // generally everything is not allowed
        $allowed = false;

        if ($accesses['allow']) {
            // check allow accesses informations if something matches
            foreach ($accesses['allow'] as $accessData) {

                // we are optimistic an initial say data will match
                $matchAllow = true;

                // check if accessData matches server vars
                foreach ($accessData as $serverVar => $varPattern) {

                    // check if server var exists
                    if ($requestContext->hasServerVar($serverVar)) {
                        // check if pattern matches
                        if (!preg_match(
                            '/' . $varPattern . '/',
                            $requestContext->getServerVar($serverVar)
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
        }

        if (isset($accesses['deny'])) {

            // check deny accesses informations if something matches
            foreach ($accesses['deny'] as $accessData) {

                // initial nothing denies the request
                $matchDeny = false;

                // check if accessData matches server vars
                foreach ($accessData as $serverVar => $varPattern) {

                    // check if server var exists
                    if ($requestContext->hasServerVar($serverVar)) {
                        // check if pattern matches
                        if (preg_match(
                            '/' . $varPattern . '/',
                            $requestContext->getServerVar($serverVar)
                        )) {
                            $matchDeny = true;
                            // break here if anything matches
                            break;
                        }
                    }
                }

                if ($matchDeny) {
                    // set allowed flag false
                    $allowed = false;
                    // break here cause' we found an allowed access
                    break;
                }
            }
        }

        // check if it's finally not allowed
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
