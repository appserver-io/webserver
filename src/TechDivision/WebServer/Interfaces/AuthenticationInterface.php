<?php
/**
 * \TechDivision\WebServer\Interfaces\AuthenticationInterface
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
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Interfaces;

use TechDivision\WebServer\Exceptions\ModuleException;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;

/**
 * Interface AuthenticationInterface
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
interface AuthenticationInterface
{

    /**
     * Initialise by the auth content got from client
     *
     * @param string $authData The content of authentication data sent by client
     *
     * @return void
     */
    public function init($authData);

    /**
     * Try to authenticate
     *
     * @param string $username The username to match auth for
     * @param string $password The password to match auth for
     *
     * @return bool If auth was successful return true if no false will be returned
     */
    public function auth($username, $password);
}
