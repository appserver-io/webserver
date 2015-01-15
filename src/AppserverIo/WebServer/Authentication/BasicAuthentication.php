<?php

/**
 * \AppserverIo\WebServer\Authentication\BasicAuthentication
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
 * Class BasicAuthentication
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Authentication
 * @author     Johann Zelger <jz@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
class BasicAuthentication extends AbstractAuthentication implements AuthenticationInterface
{
    /**
     * Defines the auth type which should match the client request type definition
     *
     * @var string
     */
    const AUTH_TYPE = 'Basic';

    /**
     * Parses the header content set in init before
     *
     * @return bool If parsing was successful
     */
    protected function parse()
    {
        // set auth hash got from auth data request header
        $this->authHash = trim(strstr($this->getAuthData(), " "));
        // get out username and password
        list($this->username, $this->password) = explode(':', base64_decode($this->authHash));
        // check if either username or password was not found and return false
        if (($this->password === null) || ($this->username === null)) {
            return false;
        }
        return true;
    }

    /**
     * Returns the authentication header for response to set
     *
     * @return string
     */
    public function getAuthenticateHeader()
    {
        return $this->getType() . ' realm="' . $this->configData["realm"] . "'";
    }

    /**
     * Inits the credentials by given file in config
     *
     * @return void
     */
    public function initCredentials()
    {
        // get file content
        $fileLines = file($this->configData['file']);
        // iterate all lines and set credentials
        foreach ($fileLines as $fileLine) {
            list($user, $pass) = explode(':', $fileLine);
            $this->credentials[trim($user)] = trim($pass);
        }
    }

    /**
     * Try to authenticate
     *
     * @return bool If auth was successful return true if no false will be returned
     * @throws AuthenticationException
     */
    public function auth()
    {
        // set internal var refs
        $credentials = $this->getCredentials();
        // verify everything to be ready for auth if not return false
        if (!$this->verify()) {
            return false;
        };
        // do auth check
        return (password_verify($this->getPassword(), $credentials[$this->getUsername()]));
    }
}
