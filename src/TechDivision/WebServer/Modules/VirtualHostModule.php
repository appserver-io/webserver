<?php
/**
 * \TechDivision\WebServer\Modules\VirtualHostModule
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
use TechDivision\WebServer\Dictionaries\ModuleHooks;
use TechDivision\WebServer\Dictionaries\ServerVars;
use TechDivision\WebServer\Dictionaries\ModuleVars;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\WebServer\Interfaces\ModuleInterface;
use TechDivision\WebServer\Modules\ModuleException;
use TechDivision\WebServer\Interfaces\ServerContextInterface;

/**
 * Class VirtualHostModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@techdivision.com>
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class VirtualHostModule implements ModuleInterface
{
    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'virtualHost';

    /**
     * Defines the map from params to server vars
     *
     * @var array
     */
    protected $paramServerVarsMap = array(
        'admin' => ServerVars::SERVER_ADMIN,
        'documentRoot' => ServerVars::DOCUMENT_ROOT,
        'software' => ServerVars::SERVER_SOFTWARE,
        'errorsPageTemplatePath' => ServerVars::SERVER_ERRORS_PAGE_TEMPLATE_PATH
    );

    /**
     * Hold's the server context instance
     *
     * @var \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    protected $serverContext;

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

        // set req and res object internally
        $this->request = $request;
        $this->response = $response;

        $virtualHosts = $this->getServerContext()->getServerConfig()->getVirtualHosts();
        $serverName = $this->getServerContext()->getServerVar(ServerVars::SERVER_NAME);

        // check if current host matches any virtual host configuration
        if (isset($virtualHosts[$serverName])) {
            // read out params
            $params = $virtualHosts[$serverName]['params'];
            // iterate over all params and try to set as server var via mapping
            foreach ($params as $paramName => $paramValue) {
                // check if server var mapping exists
                if (isset($this->paramServerVarsMap[$paramName])) {
                    // check if documentRoot is changed
                    if ($this->paramServerVarsMap[$paramName] === ServerVars::DOCUMENT_ROOT) {
                        // check if relative path is given and make is absolute by using cwd as prefix
                        if (substr($paramValue, 0, 1) !== "/") {
                            $paramValue = getcwd() . DIRECTORY_SEPARATOR . $paramValue;
                        }
                    }
                    // set server var
                    $this->getServerContext()->setServerVar(
                        $this->paramServerVarsMap[$paramName],
                        $paramValue
                    );
                }
            }

            // Add the rewrites we have (if any) to the configuration's rewrite pool
            if (!empty($virtualHosts[$serverName]['rewrites'])) {
                // Set the rewrites we encountered as a temporary module var
                $this->serverContext->setModuleVar(
                    ModuleVars::VOLATILE_REWRITES,
                    $virtualHosts[$serverName]['rewrites']
                );
            }

            // Add the environment vars we have (if any) to the configuration's environment variable pool
            if (!empty($virtualHosts[$serverName]['environmentVariables'])) {
                // Set the environment variables we encountered as a temporary module var
                $this->serverContext->setModuleVar(
                    ModuleVars::VOLATILE_ENVIRONMENT_VARIABLES,
                    $virtualHosts[$serverName]['environmentVariables']
                );
            }

            // Add the accesses (if any) to the configuration's access pool
            if (!empty($virtualHosts[$serverName]['accesses'])) {
                // Set the environment variables we encountered as a temporary module var
                $this->serverContext->setModuleVar(
                    ModuleVars::VOLATILE_ACCESSES,
                    $virtualHosts[$serverName]['accesses']
                );
            }

            // Add the locations we have (if any) to the configuration's location pool
            if (!empty($virtualHosts[$serverName]['locations'])) {
                // Set the locations we encountered as a temporary module var
                $this->serverContext->setModuleVar(
                    ModuleVars::VOLATILE_LOCATIONS,
                    $virtualHosts[$serverName]['locations']
                );
            }

            // Add the rewriteMaps we have (if any) to the configuration's rewriteMaps pool
            if (!empty($virtualHosts[$serverName]['rewriteMaps'])) {
                // Set the rewriteMaps we encountered as a temporary module var
                $this->serverContext->setModuleVar(
                    ModuleVars::VOLATILE_REWRITE_MAPS,
                    $virtualHosts[$serverName]['rewriteMaps']
                );
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
}
