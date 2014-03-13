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
use TechDivision\Http\HttpResponseStates;
use TechDivision\WebServer\Authentication\BasicAuthentication;
use TechDivision\WebServer\Dictionaries\ServerVars;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\WebServer\Interfaces\AuthenticationInterface;
use TechDivision\WebServer\Interfaces\ModuleInterface;
use TechDivision\WebServer\Exceptions\ModuleException;
use TechDivision\WebServer\Interfaces\ServerContextInterface;

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
     * @var \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    protected $serverContext;

    /**
     * Hold's all authenticationType given by config in server's context
     *
     * @var array
     */
    protected $authenticationTypes;

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
        $this->serverContext= $serverContext;
        $this->authentications = $serverContext->getServerConfig()->getAuthentications();

        // check if authentication are given
        if (is_array($this->authentications)) {
            // init all types
            foreach ($this->authentications as $authType => $params) {
                $authTypeInstance = new $authType();
                $this->authenticationTypes[$authTypeInstance::AUTH_TYPE] = $authTypeInstance;
            }
        }
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
     * Return's pre initiated auth type instance by given type
     *
     * @param string $authType The authentication type
     * @param string $authData The data got from client for authentication process
     *
     * @return \TechDivision\WebServer\Interfaces\AuthenticationInterface
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function getAuthenticationTypeInstance($authType, $authData)
    {
        if (isset($this->authenticationTypes[$authType])) {
            // init auth type by given auth data
            $this->authenticationTypes[$authType]->init($authData);
            return $this->authenticationTypes[$authType];
        }
        throw new ModuleException("No auth type found for given request '$authType'", 500);
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

        // check authentication informations if something matches
        foreach ($this->authentications as $authentication) {
            // check if pattern matches uri
            if (preg_match('/'.$authentication["pattern"].'/', $request->getUri())) {
                // check if client sends an authentication header
                if ($authHeader = $request->getHeader(HttpProtocol::HEADER_AUTHORIZATION)) {
                    // get auth type and content out of header
                    list($authType, $authData) = explode(' ', $authHeader);
                    // get corresponding authenticator for this auth type
                    $authenticator = $this->getAuthenticationTypeInstance($authType, $authData);
                    // check if auth works with given credentials
                    if ($authenticator->auth($authentication["username"], $authentication["password"])) {
                        $response->appendBodyStream('OK');
                    } else {
                        $response->setStatusCode(401);
                        $response->appendBodyStream('NOK');
                        $response->addHeader(HttpProtocol::HEADER_WWW_AUTHENTICATE, 'Basic realm="Test Authentication System"');
                    }

                    $response->addHeader(HttpProtocol::HEADER_CONTENT_TYPE,'text/plain');
                    $response->setState(HttpResponseStates::DISPATCH);

                } else {

                    $response->addHeader(HttpProtocol::HEADER_WWW_AUTHENTICATE, 'Basic realm="Test Authentication System"');
                    $response->setStatusCode(401);
                    $response->setState(HttpResponseStates::DISPATCH);
                }

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
