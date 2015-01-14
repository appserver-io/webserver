<?php

/**
 * \AppserverIo\WebServer\Interfaces\AuthenticationInterface
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
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */

namespace AppserverIo\WebServer\Interfaces;

use AppserverIo\Server\Exceptions\ModuleException;

/**
 * Interface AuthenticationInterface
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
interface AuthenticationInterface
{
    /**
     * Constructs the authentication type
     *
     * @param array $configData The configuration data for auth type instance
     */
    public function __construct(array $configData = array());

    /**
     * Initialise by the auth content got from client
     *
     * @param string $authData  The content of authentication data sent by client
     * @param string $reqMethod The https request method as string
     *
     * @return void
     */
    public function init($authData, $reqMethod);

    /**
     * Try to authenticate
     *
     * @param array $credentialData The credential data the auth implementation expects
     *
     * @return bool If auth was successful return true if no false will be returned
     */
    public function auth();

    /**
     * Return's the authentication type token to compare with request header
     *
     * @return string
     */
    public function getType();

    /**
     * Return's the parsed username
     *
     * @return string
     */
    public function getUsername();

    /**
     * Returns the authentication header for response to set
     *
     * @return string
     */
    public function getAuthenticateHeader();
}
