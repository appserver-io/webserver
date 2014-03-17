<?php
/**
 * \TechDivision\WebServer\Authentication\BasicAuthentication
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
 * @subpackage Authentication
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Authentication;

use TechDivision\WebServer\Interfaces\AuthenticationInterface;

/**
 * Class BasicAuthentication
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Authentication
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class BasicAuthentication implements AuthenticationInterface
{
    /**
     * Defines the auth type which should match the client request type definition
     *
     * @var string
     */
    const AUTH_TYPE = 'Basic';

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
     * Initialise by the authentication data given by client
     *
     * @param string $authData The content of authentication header sent by client
     *
     * @return void
     */
    public function init($authData)
    {
        $this->authData = $authData;
        $this->parse();
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
     * Try to authenticate
     *
     * @param array $credentialData The credential data to auth against
     *
     * @return bool If auth was successful return true if no false will be returned
     */
    public function auth(array $credentialData)
    {
        if ($this->getAuthHash() === $credentialData["hash"]) {
            return true;
        }
        // todo: check if hashFile is given and try to auth against
        // todo: check if username password combination is given and try to auth against
        return false;
    }

    /**
     * Return's the authentication type token
     *
     * @return string
     */
    public function getType()
    {
        return self::AUTH_TYPE;
    }
}
