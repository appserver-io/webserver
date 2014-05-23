<?php
/**
 * \TechDivision\WebServer\Modules\AuthenticationModule
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
use TechDivision\Server\Dictionaries\ModuleHooks;
use TechDivision\Server\Dictionaries\ServerVars;
use TechDivision\Server\Interfaces\ModuleInterface;
use TechDivision\Server\Exceptions\ModuleException;
use TechDivision\Server\Interfaces\ServerContextInterface;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\WebServer\Interfaces\AuthenticationInterface;
use TechDivision\WebServer\Authentication\BasicAuthentication;

/**
 * Class AuthenticationModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class AuthenticationModule implements ModuleInterface
{
    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'authentication';

    /**
     * Hold's the server context instance
     *
     * @var \TechDivision\Server\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Hold's all authenticationType instances used within server while running
     *
     * @var array
     */
    protected $typeInstances;

    /**
     * Hold's an array of all uri patterns to match uri for
     *
     * @var array
     */
    protected $uriPatterns;

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
     * @param \TechDivision\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
        $this->serverContext = $serverContext;
        $this->authentications = $serverContext->getServerConfig()->getAuthentications();

        // check if authentication are given
        if (is_array($this->authentications)) {
            // init all uri patterns
            foreach ($this->authentications as $uri => $params) {
                $this->uriPatterns[$uri] = $params;
                // pre init types by calling getter in init
                $this->getAuthenticationTypeInstance($params["type"], $params);
            }
        }
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
     * Return's pre initiated auth type instance by given type
     *
     * @param string $type The authentication type
     * @param string $data The data got from client for authentication process
     *
     * @return \TechDivision\Server\Interfaces\AuthenticationInterface
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function getAuthenticationTypeInstance($type, array $data = array())
    {
        if (!is_object($this->typeInstances[$type])) {
            // check if type class does not exist
            if (!class_exists($type)) {
                throw new ModuleException("No auth type found for '$type'", 500);
            }
            // init type by given class definition and data
            $this->typeInstances[$type] = new $type();
        }
        return $this->typeInstances[$type];
    }

    /**
     * Implement's module logic for given hook
     *
     * @param \TechDivision\Http\HttpRequestInterface  $request  The request object
     * @param \TechDivision\Http\HttpResponseInterface $response The response object
     * @param int                                      $hook     The current hook to process logic for
     *
     * @return bool
     * @throws \TechDivision\Server\Exceptions\ModuleException
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

        // check authentication informations if something matches
        foreach ($this->authentications as $uriPattern => $data) {
            // check if pattern matches uri
            if (preg_match(
                '/' . $uriPattern . '/',
                $this->getServerContext()->getServerVar(ServerVars::X_REQUEST_URI)
            )
            ) {
                // set type Instance to local ref
                $typeInstance = $this->getAuthenticationTypeInstance($data["type"]);
                // check if client sends an authentication header
                if ($authHeader = $request->getHeader(HttpProtocol::HEADER_AUTHORIZATION)) {
                    // init type instance by auth header
                    $typeInstance->init($authHeader);
                    // check if auth works
                    if ($typeInstance->auth($data)) {
                        // set server vars
                        $this->getServerContext()->setServerVar(ServerVars::REMOTE_USER, $typeInstance->getUsername());
                        // break out because everything is fine at this point
                        return true;
                    }
                }
                // send header for challenge authentication against client
                $response->addHeader(
                    HttpProtocol::HEADER_WWW_AUTHENTICATE,
                    $typeInstance->getType() . ' realm="' . $data["realm"] . "'"
                );
                // throw exception for auth required
                throw new ModuleException(null, 401);
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
