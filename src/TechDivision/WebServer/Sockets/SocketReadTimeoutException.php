<?php
/**
 * \TechDivision\WebServer\Sockets\SocketReadTimeoutException
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Library
 * @package    TechDivision_WebServer
 * @subpackage Sockets
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Sockets;

/**
 * Class SocketReadTimeoutException
 *
 * @category   Library
 * @package    TechDivision_WebServer
 * @subpackage Sockets
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class SocketReadTimeoutException extends \Exception
{

    /**
     * Defines message for exception
     *
     * @var string The message
     */
    protected $message = 'Read timeout occured';
}
