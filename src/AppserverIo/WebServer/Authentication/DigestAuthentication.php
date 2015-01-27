<?php

/**
 * \AppserverIo\WebServer\Authentication\DigestAuthentication
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io
 */
namespace AppserverIo\WebServer\Authentication;

use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\WebServer\Interfaces\AuthenticationInterface;

/**
 * Class DigestAuthentication
 *
 * @author Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link https://github.com/appserver-io/webserver
 * @link http://www.appserver.io
 */
class DigestAuthentication extends AbstractAuthentication implements AuthenticationInterface
{

    /**
     * Defines the auth type which should match the client request type definition
     *
     * @var string
     */
    const AUTH_TYPE = 'Digest';

    /**
     * Parses the header content set in init before
     *
     * @return bool If parsing was successful
     */
    protected function parse()
    {
        // init data var
        $data = array();
        
        // define required data
        $requiredData = array(
            'nonce' => 1,
            'nc' => 1,
            'cnonce' => 1,
            'qop' => 1,
            'username' => 1,
            'uri' => 1,
            'response' => 1
        );
        
        // prepare key for parsing logic
        $key = implode('|', array_keys($requiredData));
        
        // parse header value
        preg_match_all('@(' . $key . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $this->authData, $matches, PREG_SET_ORDER);
        
        // iterate all found values for header value
        foreach ($matches as $match) {
            
            // check if match could be found
            if ($match[3]) {
                $data[$match[1]] = $match[3];
            } else {
                $data[$match[1]] = $match[4];
            }
            
            // unset required value because we got it processed
            unset($requiredData[$match[1]]);
        }
        
        // set if all required data was processed
        $this->authData = $requiredData ? false : $data;
        $this->username = $this->authData["username"];
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
            list ($user, $realm, $pass) = explode(':', $fileLine);
            $this->credentials[trim($user)] = trim($pass);
        }
    }

    /**
     * Returns the authentication header for response to set
     *
     * @return string
     */
    public function getAuthenticateHeader()
    {
        return $this->getType() . ' realm="' . $this->configData["realm"] . '",qop="auth",nonce="' . uniqid() . '",opaque="' . md5($this->configData["realm"]) . '"';
    }

    /**
     * Try to authenticate
     *
     * @return bool If auth was successful return true if no false will be returned
     */
    public function auth()
    {
        // set internal var refs
        $credentials = $this->getCredentials();
        $authData = $this->getAuthData();
        
        // verify everything to be ready for auth if not return false
        if (! $this->verify()) {
            return false;
        }
        ;
        // create valid response data
        $ha1 = $credentials[$this->getUsername()];
        $ha2 = md5(implode(':', array(
            $this->getReqMethod() . ':' . $authData['uri']
        )));
        
        // create valid response to compare with auth data response
        $validResponse = md5(implode(':', array(
            $ha1,
            $authData['nonce'],
            $authData['nc'],
            $authData['cnonce'],
            $authData['qop'],
            $ha2
        )));
        // compare response with valid response
        return $authData['response'] === $validResponse;
    }
}
