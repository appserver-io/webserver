<?php
/**
 * \TechDivision\WebServer\Sockets\SocketInterface
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
 * Interface SocketInterface
 *
 * @category   Library
 * @package    TechDivision_WebServer
 * @subpackage Sockets
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
interface SocketInterface
{

    /**
     * Creates a stream socket server and returns a instance of Stream implementation with server socket in it.
     *
     * @param string $address The address the server should be listening to. For example 0.0.0.0:8080
     *
     * @return \TechDivision\WebServer\Sockets\SocketInterface The Stream instance with a server socket created.
     */
    public static function getServerInstance($address);

    /**
     * Return's an instance of Stream with preset resource in it.
     *
     * @param resource $connectionResource The resource to use
     *
     * @return \TechDivision\WebServer\Sockets\StreamSocket
     */
    public static function getInstance($connectionResource);

    /**
     * Accepts connections from clients and build up a instance of Stream with connection resource in it.
     *
     * @param int $acceptTimeout  The timeout in seconds to wait for accepting connections.
     * @param int $receiveTimeout The timeout in seconds to wait for read a line.
     *
     * @return \TechDivision\WebServer\Sockets\SocketInterface The Stream instance with the connection socket accepted.
     */
    public function accept($acceptTimeout = 600, $receiveTimeout = 60);

    /**
     * Return's the line read from connection resource
     *
     * @param int $readLength The max length to read for a line.
     *
     * @return string;
     */
    public function readLine($readLength = 256);

    /**
     * Read's the given length from connection resource
     *
     * @param int $readLength     The max length to read for a line.
     * @param int $receiveTimeout The max time to wait for read the next line
     *
     * @return string;
     * @throws \TechDivision\WebServer\Sockets\SocketReadTimeoutException
     */
    public function read($readLength = 256, $receiveTimeout = null);

    /**
     * Writes the given message to the connection resource.
     *
     * @param string $message The message to write to the connection resource.
     *
     * @return int
     */
    public function write($message);

    /**
     * Copies data from a stream
     *
     * @param resource $sourceResource The source stream
     *
     * @return int The total count of bytes copied.
     */
    public function copyStream($sourceResource);

    /**
     * Closes the connection resource
     *
     * @return bool
     */
    public function close();

    /**
     * Set's the connection resource
     *
     * @param resource $connectionResource The resource for socket file descriptor
     *
     * @return void
     */
    public function setConnectionResource($connectionResource);

    /**
     * Return's the connection resource
     *
     * @return mixed
     */
    public function getConnectionResource();
}
