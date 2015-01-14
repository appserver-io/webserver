<?php

/**
 * \AppserverIo\WebServer\Authentication\AbstractAuthentication
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Authentication
 * @author     Johann Zelger <jz@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */

namespace AppserverIo\WebServer\Authentication;

use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\WebServer\Interfaces\AuthenticationInterface;

/**
 * Class AbstractAuthentication
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Authentication
 * @author     Johann Zelger <jz@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
class AbstractAuthentication
{
    /**
     * Holds the valid credentials given in passwd file
     *
     * @var array
     */
    protected $credentials;

    /**
     * The parsed username given by header content payload
     *
     * @var string
     */
    protected $username;

    /**
     * The password given by header content payload
     *
     * @var string
     */
    protected $password;

    /**
     * Hold's the auth data got from http authentication header
     *
     * @var string
     */
    protected $authData;

    /**
     * Hold's the auth hash to compare with auth information given by system
     *
     * @var string
     */
    protected $authHash;

    /**
     * Hold's the requests method
     *
     * @var string
     */
    protected $reqMethod;

    /**
     * Hold's the configuration data given for authentication type
     *
     * @var array
     */
    protected $configData;

    /**
     * Constructs the authentication type
     *
     * @param array $configData The configuration data for auth type instance
     */
    public function __construct(array $configData = array())
    {
        // set vars internally
        $this->configData = $configData;
        // init credentials
        $this->initCredentials();
    }

    /**
     * Initialise by the authentication data given by client
     *
     * @param string $authData The content of authentication header sent by client
     *
     * @return void
     */
    public function init($authData, $reqMethod)
    {
        // set vars internally
        $this->authData = $authData;
        $this->reqMethod = $reqMethod;

        // parse auth data
        $this->parse();
    }

    /**
     * Return's the request method
     *
     * @return string The request method
     */
    public function getReqMethod()
    {
        return $this->reqMethod;
    }

    /**
     * Return's the authentication data content
     *
     * @return string The authentication data content
     */
    public function getAuthData()
    {
        return $this->authData;
    }

    /**
     * Return's the auth hash got from request parsing
     *
     * @return string
     */
    public function getAuthHash()
    {
        return $this->authHash;
    }

    /**
     * Return's the authentication type token
     *
     * @return string
     */
    public function getType()
    {
        return $this::AUTH_TYPE;
    }

    /**
     * Return's the parsed username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Return's the parsed password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Return's the parsed password
     *
     * @return string
     */
    public function getCredentials()
    {
        return $this->credentials;
    }
}
