<?php

namespace TechDivision\WebServer\Authentication;

use TechDivision\WebServer\Interfaces\AuthenticationInterface;

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
     * Parses the header content set in init before
     *
     * @return bool If parsing was successful
     */
    protected function parse()
    {
        // set auth hash got from auth data request header
        $authHash = $this->getAuthData();
        // get out username and password
        list($this->username, $this->password) = explode(':', base64_decode($authHash));
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
     * Try to authenticate
     *
     * @param string $username The username to match auth for
     * @param string $password The password to match auth for
     *
     * @return bool If auth was successful return true if no false will be returned
     */
    public function auth($username, $password)
    {
        if (($username === $this->username) && ($password === $this->password)) {
            return true;
        }
    }
}
